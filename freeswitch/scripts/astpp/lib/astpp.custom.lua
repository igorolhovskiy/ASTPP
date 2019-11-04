
function did_fix_query_austrian(field, dialed_number, offset)
    local num_length = string.len(dialed_number)
    if offset >= num_length then
        offset = num_length - 1
    end
    local query_string = "("..field.." = \""..dialed_number.."\""
    for i = -2, -offset, -1 do
        query_string = query_string.." OR "..field.." = \""..string.sub(dialed_number, 1, i).."\""
    end
    query_string = query_string.." OR "..field.." = \""..string.sub(dialed_number, 1, -offset - 1).."\")"

    return query_string
end

--- Get CallerID normalization for support of {ani} and {name} keyword
function normalize_callerid_ani(callerid)
    if (callerid ~= nil) then
        callerid_override_name = callerid['cid_name']
        callerid_override_number = callerid['cid_number']
        if (callerid_override_name:find('{ani}')) then
            Logger.debug("[Functions][NORMALIZE_CALLERID_ANI] {ani} in callerid_name found")
            callerid_name = params:getHeader('Caller-Caller-ID-Name') or ""
            callerid_name = callerid_name:match("%d+") or ""
            callerid_override_name = callerid_override_name:gsub("{ani}",callerid_name)
        end
        if (callerid_override_number:find('{name}')) then
            Logger.debug("[Functions][NORMALIZE_CALLERID_ANI] {name} in callerid_number found")
            callerid_override_number = callerid_override_name
        elseif (callerid_override_number:find('{ani}')) then
            Logger.debug("[Functions][NORMALIZE_CALLERID_ANI] {ani} in callerid_number found")
            callerid_number = params:getHeader('Caller-Caller-ID-Number') or ""
            callerid_number = callerid_number:match("%d+") or ""
            callerid_override_number = callerid_override_number:gsub("{ani}",callerid_number)
        end
        if (callerid_override_name:find('{number}')) then
            Logger.debug("[Functions][NORMALIZE_CALLERID_ANI] {number} in callerid_name found")
            callerid_override_name = callerid_override_number
        end
        Logger.debug("[Functions][NORMALIZE_CALLERID_ANI] CallerID name: "..callerid_override_name..", number: "..callerid_override_number)
        result = {}
        result['cid_name'] = callerid_override_name
        result['cid_number'] = callerid_override_number
        return result
    end
    return nil
end

-- Check DID info OVERRIDE
function check_did(destination_number,config)
	local did_localization = nil 
	if (config['did_global_translation'] ~= nil and config['did_global_translation'] ~= '' and tonumber(config['did_global_translation']) > 0) then
		did_localization = get_localization(config['did_global_translation'],'O')
		-- @TODO: Apply localization logic for DID global translation
		if (did_localization ~= nil) then
			did_localization['number_originate'] = did_localization['number_originate']:gsub(" ", "")
			destination_number = do_number_translation(did_localization['number_originate'],destination_number)
		end
	end
	--TODO Change query for check DID avilable or not using left join.
	local query = "SELECT A.id as id,A.number as did_number,B.id as accountid,B.number as account_code,A.number as did_number,A.connectcost,A.includedseconds,A.cost,A.inc,A.extensions,A.maxchannels,A.call_type,A.city,A.province,A.init_inc,A.leg_timeout,A.status,A.country_id,A.call_type_vm_flag FROM "..TBL_DIDS.." AS A,"..TBL_USERS.." AS B WHERE B.status=0 AND B.deleted=0 AND B.id=A.accountid AND A.number =\"" ..destination_number .."\" LIMIT 1";

	-- Version from 3.0m	   
	-- local query = "SELECT A.id as id,B.id as accountid,B.number as account_code,A.number as  did_number,A.connectcost,A.includedseconds,A.cost,A.inc,A.extensions,A.maxchannels,A.call_type,A.city,A.province,A.init_inc FROM "..TBL_DIDS.." AS A,"..TBL_USERS.." AS B WHERE A.status=0 AND B.status=0 AND B.deleted=0 AND B.id=A.accountid AND "..did_fix_query_austrian("A.number", destination_number, 5).." LIMIT 1";

	Logger.debug("[CHECK_DID] Query :" .. query)
	assert (dbh:query(query, function(u)
		didinfo = u;	 
		-- B.did_cid_translation as did_cid_translation,
		if (did_localization ~= nil) then	
			did_localization['in_caller_id_originate'] = did_localization['in_caller_id_originate']:gsub(" ", "")
			didinfo['did_cid_translation'] = did_localization['in_caller_id_originate']
		else
			didinfo['did_cid_translation'] = ""
		end
	end))
	return didinfo;
end
-- check Reseller DID OVERRIDE
function check_did_reseller(destination_number,userinfo,config)
	local number_translation 
	number_translation = config['did_global_translation'];
	destination_number = do_number_translation(number_translation,destination_number)   
	local query = "SELECT A.id as id, A.number AS number,B.cost AS cost,B.connectcost AS connectcost,B.includedseconds AS includedseconds,B.inc AS inc,A.city AS city,A.province,A.call_type,A.extensions AS extensions,A.maxchannels AS maxchannels,A.init_inc FROM "..TBL_DIDS.." AS A,"..TBL_RESELLER_PRICING.." as B WHERE A.number = \"" ..destination_number .."\"  AND B.type = '1' AND B.reseller_id = \"" ..userinfo['reseller_id'].."\" AND B.note =\"" ..destination_number .."\"";

	-- Version from 3.0m
	-- local query = "SELECT A.id as id, A.number AS number,B.cost AS cost,B.connectcost AS connectcost,B.includedseconds AS includedseconds,B.inc AS inc,A.city AS city,A.province,A.call_type,A.extensions AS extensions,A.maxchannels AS maxchannels,A.init_inc FROM "..TBL_DIDS.." AS A,"..TBL_RESELLER_PRICING.." as B WHERE "..did_fix_query_austrian("A.number", destination_number, 5).." AND B.type = '1' AND B.reseller_id = \"" ..userinfo['reseller_id'].."\" AND "..did_fix_query_austrian("B.note", destination_number, 5);

	Logger.debug("[CHECK_DID_RESELLER] Query :" .. query)
	assert (dbh:query(query, function(u)
		didinfo = u;
	end))
	return didinfo;
end

-- Dialplan for outbound calls OVERRIDE
function freeswitch_xml_outbound(xml,destination_number,outbound_info,callerid_array,rate_group_id,old_trunk_id,force_outbound_routes,rategroup_type,livecall_data)

	local temp_destination_number = destination_number
	local tr_localization=nil
	tr_localization = get_localization(outbound_info['provider_id'],'T')

	
	if (tr_localization ~= nil) then
		tr_localization['out_caller_id_terminate'] = tr_localization['out_caller_id_terminate']:gsub(" ", "")
		-------------- Caller Id translation ---------	 
		callerid_array['cid_name'] = do_number_translation(tr_localization['out_caller_id_terminate'],callerid_array['cid_name'])
		callerid_array['cid_number'] = do_number_translation(tr_localization['out_caller_id_terminate'],callerid_array['cid_number'])    
		xml = freeswitch_xml_callerid(xml,callerid_array)	    	   	    
    	----------------------------------------------------------------------

    	-------------- Destination number translation ---------
		tr_localization['number_terminate'] = tr_localization['number_terminate']:gsub(" ", "")
		temp_destination_number = do_number_translation(tr_localization['number_terminate'],destination_number)
		-----------------------------------
		
	end

	if(outbound_info['prepend'] ~= '' or outbound_info['strip'] ~= '') then

        if (outbound_info['prepend'] == '') then 
            outbound_info['prepend'] = '*'                        
        end

        if (outbound_info['strip'] == '') then 
            outbound_info['strip'] = '*'
        end

		temp_destination_number = do_number_translation(outbound_info['strip'].."/"..outbound_info['prepend'],temp_destination_number)
	end
    
	xml_termiantion_rates= "ID:"..outbound_info['outbound_route_id'].."|CODE:"..outbound_info['pattern'].."|DESTINATION:"..outbound_info['comment'].."|CONNECTIONCOST:"..outbound_info['connectcost'].."|INCLUDEDSECONDS:"..outbound_info['includedseconds'].."|COST:"..outbound_info['cost'].."|INC:"..outbound_info['inc'].."|INITIALBLOCK:"..outbound_info['init_inc'].."|TRUNK:"..outbound_info['trunk_id'].."|PROVIDER:"..outbound_info['provider_id'];

    table.insert(xml, [[<action application="set" data="calltype=STANDARD"/>]]);        
	table.insert(xml, [[<action application="set" data="termination_rates=]]..xml_termiantion_rates..[["/>]]);        
	table.insert(xml, [[<action application="set" data="trunk_id=]]..outbound_info['trunk_id']..[["/>]]);        
	table.insert(xml, [[<action application="set" data="provider_id=]]..outbound_info['provider_id']..[["/>]]);           
	table.insert(xml, [[<action application="set" data="rate_flag=]]..rategroup_type..[["/>]]);           
	table.insert(xml, [[<action application="set" data="force_trunk_flag=]]..force_outbound_routes..[["/>]]);    
    table.insert(xml, [[<action application="export" data="presence_data=trunk_id=]]..outbound_info['trunk_id']..[["/>]])
    table.insert(xml, [[<action application="set" data="intcall=]]..(outbound_info['intcall'] and 1 or 0)..[["/>]])

	-- Check if is there any gateway configuration params available for it.
	if (outbound_info['dialplan_variable'] ~= '') then 
		Logger.info(" ".. outbound_info['dialplan_variable']);
		local dialplan_variable = split(outbound_info['dialplan_variable'],",")      
		for dialplan_variable_key,dialplan_variable_value in pairs(dialplan_variable) do
			local dialplan_variable_data = split(dialplan_variable_value,"=")  
			Logger.debug("[GATEWAY VARIABLE ] : "..dialplan_variable_data[1] )
            if( dialplan_variable_data[1] ~= nil and dialplan_variable_data[2] ~= nil) then
                if (dialplan_variable_data[1] == 'force_callback') then
                    callback_function_name = dialplan_variable_data[2]
                    Logger.debug("[GATEWAY VARIABLE] : Callback triggered to "..callback_function_name)
                    if (_G[callback_function_name] ~= nil) then
                        xml, temp_destination_number = _G[callback_function_name](xml, temp_destination_number, callerid_array)
                    end
                end
                table.insert(xml, [[<action application="set" data="]]..dialplan_variable_data[1].."="..dialplan_variable_data[2]..[["/>]])
			end
		end             
	end
	----------------------- END Gateway configuraiton -------------------------------
	-- Set force codec if configured
	
	--~ livecall_data = livecall_data.."|||"..outbound_info['trunk_name'].." // "..outbound_info['pattern'].." // "..outbound_info['comment'].." // "..outbound_info['cost']
	
	if(outbound_info['trunk_id']~=nil) then
		livecall_data = livecall_data.."|||"..outbound_info['trunk_name'].." // "..outbound_info['pattern'].." // "..outbound_info['comment'].." // "..outbound_info['cost'].." // trunk_id="..outbound_info['trunk_id']
	else
		livecall_data = livecall_data.."|||"..outbound_info['trunk_name'].." // "..outbound_info['pattern'].." // "..outbound_info['comment'].." // "..outbound_info['cost']
	end
	
    table.insert(xml, [[<action application="export" data="presence_data=]]..livecall_data..[[|||STD"/>]])
	
    chan_var = "leg_timeout="..outbound_info['leg_timeout']
    if (outbound_info['codec'] ~= '') then
            chan_var = chan_var..",absolute_codec_string=".."^^:"..outbound_info['codec']:gsub("%,", ":")
    end            

	-- Set CPS limit for user if > 0
	if (tonumber(outbound_info['cps']) ~=nil and tonumber(outbound_info['cps']) > 0) then
		table.insert(xml, [[<action application="limit" data="hash CPS_]]..outbound_info['trunk_id']..[[ CPS_trunk_]]..outbound_info['trunk_id']..[[ ]]..outbound_info['cps']..[[/1 !SWITCH_CONGESTION"/>]]);
	end

	if(tonumber(outbound_info['maxchannels']) > 0) then    
		table.insert(xml, [[<action application="limit_execute" data="db ]]..outbound_info['path']..[[ gw_]]..outbound_info['path']..[[ ]]..outbound_info['maxchannels']..[[ bridge []]..chan_var..[[]sofia/gateway/]]..outbound_info['path']..[[/]]..temp_destination_number..[["/>]]);
	else
		table.insert(xml, [[<action application="bridge" data="[]]..chan_var..[[]sofia/gateway/]]..outbound_info['path']..[[/]]..temp_destination_number..[["/>]]);
	end

	if(outbound_info['path1'] ~= '' and outbound_info['path1'] ~= outbound_info['path']) then
		table.insert(xml, [[<action application="bridge" data="[]]..chan_var..[[]sofia/gateway/]]..outbound_info['path1']..[[/]]..temp_destination_number..[["/>]]);
	end

	if(outbound_info['path2'] ~= '' and outbound_info['path2'] ~= outbound_info['path'] and outbound_info['path2'] ~= outbound_info['path1']) then
		table.insert(xml, [[<action application="bridge" data="[]]..chan_var..[[]sofia/gateway/]]..outbound_info['path2']..[[/]]..temp_destination_number..[["/>]]);
	end
    return xml
end