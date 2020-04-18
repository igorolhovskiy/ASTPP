
function string:split(sep)
    
    if sep == "" then
        local fields = {}
        table.insert(fields, self)
        return fields
    end

    local sep, fields = sep or ",", {}
    local pattern = string.format("([^%s]+)", sep)

    self:gsub(pattern, function(c) fields[#fields+1] = c end)
    return fields
 end

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
            Logger.debug("[NORMALIZE_CALLERID_ANI] {ani} in callerid_name found")
            callerid_name = params:getHeader('Caller-Caller-ID-Name') or ""
            callerid_name = callerid_name:match("%d+") or ""
            callerid_override_name = callerid_override_name:gsub("{ani}",callerid_name)
        end
        if (callerid_override_number:find('{name}')) then
            Logger.debug("[NORMALIZE_CALLERID_ANI] {name} in callerid_number found")
            callerid_override_number = callerid_override_name
        elseif (callerid_override_number:find('{ani}')) then
            Logger.debug("[NORMALIZE_CALLERID_ANI] {ani} in callerid_number found")
            callerid_number = params:getHeader('Caller-Caller-ID-Number') or ""
            callerid_number = callerid_number:match("%d+") or ""
            callerid_override_number = callerid_override_number:gsub("{ani}",callerid_number)
        end
        if (callerid_override_name:find('{number}')) then
            Logger.debug("[NORMALIZE_CALLERID_ANI] {number} in callerid_name found")
            callerid_override_name = callerid_override_number
        end
        Logger.debug("[NORMALIZE_CALLERID_ANI] CallerID name: "..callerid_override_name..", number: "..callerid_override_number)
        result = {}
        result['cid_name'] = callerid_override_name
        result['cid_number'] = callerid_override_number
        return result
    end
    return nil
end

-- Check DID info OVERRIDE
function check_did(destination_number,config)

    local tmp_destination_number = destination_number

    if (config['did_global_translation'] ~= nil and config['did_global_translation'] ~= '' and tonumber(config['did_global_translation']) > 0) then

        local did_localization = get_localization(config['did_global_translation'],'O')

        if (did_localization ~= nil and did_localization['number_originate'] ~= nil and did_localization['number_originate'] ~= '') then
            did_localization['number_originate'] = did_localization['number_originate']:gsub(" ", "")
            tmp_destination_number = do_number_translation(did_localization['number_originate'], destination_number)
        end
    end
    -- 4.0.1 Original function
    -- local query = "SELECT A.id as id,A.number as did_number,B.id as accountid,B.number as account_code,A.number as did_number,A.connectcost,A.includedseconds,A.cost,A.inc,A.extensions,A.maxchannels,A.call_type,A.city,A.province,A.init_inc,A.leg_timeout,A.status,A.country_id,A.call_type_vm_flag FROM "..TBL_DIDS.." AS A,"..TBL_USERS.." AS B WHERE B.status=0 AND B.deleted=0 AND B.id=A.accountid AND A.number =\"" ..destination_number .."\" LIMIT 1";

    -- Version from 3.0m	   
    local query = "SELECT A.id as id,A.number as did_number,B.id as accountid,B.number as account_code,A.number as did_number,A.connectcost,A.includedseconds,A.cost,A.inc,A.extensions,A.maxchannels,A.call_type,A.city,A.province,A.init_inc,A.leg_timeout,A.status,A.country_id,A.call_type_vm_flag,A.localization_id,A.prepend_prefix,A.prepend_suffix FROM "..TBL_DIDS.." AS A,"..TBL_USERS.." AS B WHERE A.status=0 AND B.status=0 AND B.deleted=0 AND B.id=A.accountid AND "..did_fix_query_austrian("A.number", tmp_destination_number, 5).." ORDER BY LENGTH(A.number) DESC LIMIT 1";

    Logger.debug("[CHECK_DID_OVERRIDE] Query :" .. query)
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

    local tmp_destination_number = do_number_translation(config['did_global_translation'], destination_number)   
    
    --	4.0.1 Original function
    --	local query = "SELECT A.id as id, A.number AS number,B.cost AS cost,B.connectcost AS connectcost,B.includedseconds AS includedseconds,B.inc AS inc,A.city AS city,A.province,A.call_type,A.extensions AS extensions,A.maxchannels AS maxchannels,A.init_inc FROM "..TBL_DIDS.." AS A,"..TBL_RESELLER_PRICING.." as B WHERE A.number = \"" ..destination_number .."\"  AND B.type = '1' AND B.reseller_id = \"" ..userinfo['reseller_id'].."\" AND B.note =\"" ..destination_number .."\"";

    -- Version from 3.0m
    local query = "SELECT A.id as id, A.number AS number,B.cost AS cost,B.connectcost AS connectcost,B.includedseconds AS includedseconds,B.inc AS inc,A.city AS city,A.province,A.call_type,A.extensions AS extensions,A.maxchannels AS maxchannels,A.init_inc,A.localization_id,A.prepend_suffix,A.prepend_prefix FROM "..TBL_DIDS.." AS A,"..TBL_RESELLER_PRICING.." as B WHERE "..did_fix_query_austrian("A.number", tmp_destination_number, 5).." AND B.type = '1' AND B.reseller_id = \"" ..userinfo['reseller_id'].."\" AND "..did_fix_query_austrian("B.note", tmp_destination_number, 5) .. " ORDER BY LENGTH(A.number) DESC, LENGTH(B.note) DESC";
    
    Logger.debug("[CHECK_DID_RESELLER_OVERRIDE] Query :" .. query)

    assert (dbh:query(query, function(u)
        didinfo = u;
    end))

    return didinfo;
end


-- Get localization override for more extensive logging

function get_localization(id, type)

	local localization = nil
    local query
    
    -- O for origination
	if (type=="O") then
        query = "SELECT id,in_caller_id_originate,out_caller_id_originate,number_originate FROM "..TBL_LOCALIZATION.." WHERE id = "..id.. " AND status=0 limit 1 ";
    -- T for termination
	elseif(type=="T") then
		query = "SELECT id,out_caller_id_terminate,number_terminate FROM "..TBL_LOCALIZATION.." WHERE id=(SELECT localization_id from accounts where id = "..id.. ") AND status=0 limit 1 ";
    end
    
    Logger.debug("[GET_LOCALIZATION_OVERRIDE] Query :" .. query)

    assert (dbh:query(query, function(u)
    	localization = u
    end))

    return localization
end

-- Freeswitch XML Header OVERRIDE
function freeswitch_xml_header(xml,destination_number,accountcode,maxlength,call_direction,accountname,xml_user_rates,customer_userinfo,config,xml_did_rates,reseller_cc_limit,callerid_array,original_destination_number)
    
    local callstart = os.date("!%Y-%m-%d %H:%M:%S")

    table.insert(xml, [[<?xml version="1.0" encoding="UTF-8" standalone="no"?>]])
    table.insert(xml, [[<document type="freeswitch/xml">]])
    table.insert(xml, [[<section name="dialplan" description="ASTPP Dialplan">]])
    table.insert(xml, [[<context name="]]..params:getHeader("Caller-Context")..[[">]])
    table.insert(xml, [[<extension name="]]..destination_number..[[">]]);
    table.insert(xml, [[<condition field="destination_number" expression="]]..plus_destination_number(params:getHeader("Caller-Destination-Number"))..[[">]])
    table.insert(xml, [[<action application="set" data="effective_destination_number=]]..plus_destination_number(original_destination_number)..[["/>]])
    table.insert(xml, [[<action application="set" data="bridge_pre_execute_bleg_app=sched_hangup"/>]])
    table.insert(xml, [[<action application="set" data="bridge_pre_execute_bleg_data=+]]..((maxlength) * 60)..[[ normal_clearing"/>]])
   
    if (call_direction == "outbound" and config['realtime_billing'] == "0") then
        table.insert(xml, [[<action application="set" data="nibble_account=]]..customer_userinfo["nibble_accounts"]..[["/>]])
        table.insert(xml, [[<action application="set" data="nibble_rate=]]..customer_userinfo["nibble_rates"]..[["/>]])
        table.insert(xml, [[<action application="set" data="nibble_init_inc=]]..customer_userinfo["nibble_init_inc"]..[["/>]])
        table.insert(xml, [[<action application="set" data="nibble_inc=]]..customer_userinfo["nibble_inc"]..[["/>]])
        table.insert(xml, [[<action application="set" data="nibble_connectcost=]]..customer_userinfo["nibble_connect_cost"]..[["/>]])
        table.insert(xml, [[<action application="nibblebill" data="heartbeat 30"/>]])
    end
    
    -- Add X-Call-ID header if it's not present. Used to identify calls on both legs
    if (params:getHeader("variable_sip_h_X-Call-ID") == "" or params:getHeader("variable_sip_h_X-Call-ID") == nil) then
        table.insert(xml, [[<action application="set" data="sip_h_X-Call-ID=${sip_call_id}"/>]])
    end

    table.insert(xml, [[<action application="set" data="callstart=]]..callstart..[["/>]]);
    table.insert(xml, [[<action application="set" data="hangup_after_bridge=true"/>]]);

    -- Made it configurable if someone want to set continue_on_fail for specific disposition	
    local continue_on_fail = '!USER_BUSY'
    if (config['continue_on_fail'] ~= nil) then
        continue_on_fail = config['continue_on_fail']
    end

    table.insert(xml, [[<action application="set" data="continue_on_fail=TRUE"/>]]);  
    --table.insert(xml, [[<action application="set" data="ignore_early_media=true"/>]]);       

    table.insert(xml, [[<action application="set" data="account_id=]]..customer_userinfo['id']..[["/>]]);              
    table.insert(xml, [[<action application="set" data="parent_id=]]..customer_userinfo['reseller_id']..[["/>]]);
    table.insert(xml, [[<action application="set" data="entity_id=]]..customer_userinfo['type']..[["/>]]);
    table.insert(xml, [[<action application="set" data="call_processed=internal"/>]]);    
    table.insert(xml, [[<action application="set" data="call_direction=]]..call_direction..[["/>]]); 	
    table.insert(xml, [[<action application="set" data="accountname=]]..accountname..[["/>]]);
    if (package_id and tonumber(package_id) > 0) then
        table.insert(xml, [[<action application="set" data="package_id=]]..package_id..[["/>]]);              
    end
    if (call_direction == "inbound" and tonumber(config['inbound_fax']) > 0) then
        table.insert(xml, [[<action application="export" data="t38_passthru=true"/>]]);    
        table.insert(xml, [[<action application="set" data="fax_enable_t38=true"/>]]);    
        table.insert(xml, [[<action application="set" data="fax_enable_t38_request=true"/>]]);    
    elseif (call_direction == "outbound" and tonumber(config['outbound_fax']) > 0) then
        table.insert(xml, [[<action application="export" data="t38_passthru=true"/>]]);    
        table.insert(xml, [[<action application="set" data="fax_enable_t38=true"/>]]);    
        table.insert(xml, [[<action application="set" data="fax_enable_t38_request=true"/>]]);    
    end
    --custom outbound        
    if custom_outbound then custom_outbound(xml) end 

    if(tonumber(config['balance_announce']) == 0) then
        table.insert(xml, [[<action application="sleep" data="1000"/>]]);
        table.insert(xml, [[<action application="playback" data="/usr/share/freeswitch/sounds/en/us/callie/astpp-this-card-has-a-balance-of.wav"/>]]);
        local tmp_prefix=''
        if get_international_balance_prefix then tmp_prefix = get_international_balance_prefix(customer_userinfo) end 	

        customer_balance = tonumber(customer_userinfo['posttoexternal']) == 1 and tonumber(customer_userinfo[tmp_prefix..'credit_limit'])+(tonumber(customer_userinfo[tmp_prefix..'balance'])*(-1)) or tonumber(customer_userinfo[tmp_prefix..'balance'])

        table.insert(xml, [[<action application="say" data="en CURRENCY PRONOUNCED ]].. customer_balance..[["/>]]);

    end
    if(tonumber(config['minutes_announce']) == 0) then
        table.insert(xml, [[<action application="sleep" data="500"/>]]);
        table.insert(xml, [[<action application="playback" data="/usr/share/freeswitch/sounds/en/us/callie/astpp-this-call-will-last.wav"/>]]);
        table.insert(xml, [[<action application="say" data="en NUMBER PRONOUNCED ]].. math.floor(maxlength)..[["/>]]);
        table.insert(xml, [[<action application="playback" data="/usr/share/freeswitch/sounds/en/us/callie/astpp-minute.wav"/>]]);       
    end
    
    if (call_direction == "inbound") then 
        table.insert(xml, [[<action application="set" data="origination_rates_did=]]..xml_user_rates..[["/>]]);
    else
        table.insert(xml, [[<action application="set" data="origination_rates=]]..xml_user_rates..[["/>]]);
    end

    if(xml_did_rates ~= nil and xml_did_rates ~= '') then
        table.insert(xml, [[<action application="set" data="origination_rates=]]..xml_did_rates..[["/>]]);
    end
    
    -- Set original caller id for CDRS
    if (callerid_array['original_cid_name'] ~= '' and callerid_array['original_cid_name'] ~= '<null>')  then
            table.insert(xml, [[<action application="set" data="original_caller_id_name=]]..callerid_array['original_cid_name']..[["/>]]);
    end
    if (callerid_array['cid_number'] ~= '' and callerid_array['cid_number'] ~= '<null>')  then
            table.insert(xml, [[<action application="set" data="original_caller_id_number=]]..callerid_array['original_cid_number']..[["/>]]);
    end
       
    -- Set max channel limit for user if > 0
    if(tonumber(customer_userinfo['maxchannels']) > 0) then    		
            table.insert(xml, [[<action application="limit" data="db ]]..accountcode..[[ user_]]..accountcode..[[ ]]..customer_userinfo['maxchannels']..[[ !SWITCH_CONGESTION"/>]]);
    end

    -- Set CPS limit for user if > 0
    if (tonumber(customer_userinfo['cps']) > 0) then
        table.insert(xml, [[<action application="limit" data="hash CPS_]]..accountcode..[[ CPS_user_]]..accountcode..[[ ]]..customer_userinfo['cps']..[[/1 !SWITCH_CONGESTION"/>]]);
    end

    -- Set max channel limit for resellers
    if (reseller_cc_limit ~= nil) then
        table.insert(xml, reseller_cc_limit)
    end   

    if(tonumber(customer_userinfo['is_recording']) == 0) then 
        table.insert(xml, [[<action application="export" data="is_recording=1"/>]]);
        table.insert(xml, [[<action application="export" data="media_bug_answer_req=true"/>]]);
        table.insert(xml, [[<action application="export" data="RECORD_STEREO=true"/>]]);
        table.insert(xml, [[<action application="export" data="record_sample_rate=8000"/>]]);
        table.insert(xml, [[<action application="export" data="execute_on_answer=record_session $${recordings_dir}/${uuid}.wav"/>]]);
    end
    return xml
end

-- Dialplan for outbound calls OVERRIDE
function freeswitch_xml_outbound(xml,destination_number,outbound_info,callerid_array,rate_group_id,old_trunk_id,force_outbound_routes,rategroup_type,livecall_data)

    local temp_destination_number = destination_number
    local tr_localization=nil

    tr_localization = get_localization(outbound_info['provider_id'], 'T')
    
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
        Logger.info("[FREESWITCH_XML_OUTBOUND_OVERRIDE] Gateway variables: ".. outbound_info['dialplan_variable']);
        local dialplan_variable = split(outbound_info['dialplan_variable'],",")      
        for dialplan_variable_key,dialplan_variable_value in pairs(dialplan_variable) do
            local dialplan_variable_data = split(dialplan_variable_value,"=")  
            Logger.debug("[FREESWITCH_XML_OUTBOUND_OVERRIDE] Gateway variable: "..dialplan_variable_data[1] )
            if( dialplan_variable_data[1] ~= nil and dialplan_variable_data[2] ~= nil) then
                if (dialplan_variable_data[1] == 'force_callback') then
                    callback_function_name = dialplan_variable_data[2]
                    Logger.debug("[FREESWITCH_XML_OUTBOUND_OVERRIDE] Callback triggered to "..callback_function_name)
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
        -- Check if is there any failover gateway #1 configuration params available for it.
        if (outbound_info['dialplan_variable_1'] ~= '') then 
            Logger.info("[FREESWITCH_XML_OUTBOUND_OVERRIDE] Failover gateway #1 variables: ".. outbound_info['dialplan_variable_1']);
            local dialplan_variable = split(outbound_info['dialplan_variable_1'],",")      
            for dialplan_variable_key,dialplan_variable_value in pairs(dialplan_variable) do
                local dialplan_variable_data = split(dialplan_variable_value,"=")  
                Logger.debug("[FREESWITCH_XML_OUTBOUND_OVERRIDE] Failover gateway #1 variable: "..dialplan_variable_data[1] )
                if( dialplan_variable_data[1] ~= nil and dialplan_variable_data[2] ~= nil) then
                    if (dialplan_variable_data[1] == 'force_callback') then
                        callback_function_name = dialplan_variable_data[2]
                        Logger.debug("[FREESWITCH_XML_OUTBOUND_OVERRIDE] Callback triggered to "..callback_function_name)
                        if (_G[callback_function_name] ~= nil) then
                            xml, temp_destination_number = _G[callback_function_name](xml, temp_destination_number, callerid_array)
                        end
                    end
                    table.insert(xml, [[<action application="set" data="]]..dialplan_variable_data[1].."="..dialplan_variable_data[2]..[["/>]])
                end
            end             
        end
        table.insert(xml, [[<action application="bridge" data="[]]..chan_var..[[]sofia/gateway/]]..outbound_info['path1']..[[/]]..temp_destination_number..[["/>]]);
    end

    if(outbound_info['path2'] ~= '' and outbound_info['path2'] ~= outbound_info['path'] and outbound_info['path2'] ~= outbound_info['path1']) then
        -- Check if is there any failover gateway #2 configuration params available for it.
        if (outbound_info['dialplan_variable_2'] ~= '') then 
            Logger.info("[FREESWITCH_XML_OUTBOUND_OVERRIDE] Failover gateway #2 variables: ".. outbound_info['dialplan_variable_2']);
            local dialplan_variable = split(outbound_info['dialplan_variable_2'],",")      
            for dialplan_variable_key,dialplan_variable_value in pairs(dialplan_variable) do
                local dialplan_variable_data = split(dialplan_variable_value,"=")  
                Logger.debug("[FREESWITCH_XML_OUTBOUND_OVERRIDE] Failover gateway #2 variable: "..dialplan_variable_data[1] )
                if( dialplan_variable_data[1] ~= nil and dialplan_variable_data[2] ~= nil) then
                    if (dialplan_variable_data[1] == 'force_callback') then
                        callback_function_name = dialplan_variable_data[2]
                        Logger.debug("[FREESWITCH_XML_OUTBOUND_OVERRIDE] Callback triggered to "..callback_function_name)
                        if (_G[callback_function_name] ~= nil) then
                            xml, temp_destination_number = _G[callback_function_name](xml, temp_destination_number, callerid_array)
                        end
                    end
                    table.insert(xml, [[<action application="set" data="]]..dialplan_variable_data[1].."="..dialplan_variable_data[2]..[["/>]])
                end
            end             
        end
        table.insert(xml, [[<action application="bridge" data="[]]..chan_var..[[]sofia/gateway/]]..outbound_info['path2']..[[/]]..temp_destination_number..[["/>]]);
    end
    return xml
end

function neotel_number_normalization(xml, destination_number, calleridinfo)

    Logger.notice("[NEOTEL_NUMBER_NORMALIZATION]: Start")
    local tmp_xml = xml
    -- Cleanup destination number
    local tmp_destination_number = "+" .. destination_number:gsub("%D", "")

    -- Process callerIDinfo first
    if (calleridinfo ~= nil) then
        local callerid_name = string.lower(calleridinfo['cid_name']) or ""
        local callerid_number = calleridinfo['cid_number'] or ""

        if (callerid_number ~= "" and callerid_number:find('anon') == nil) then
            callerid_number = "+" .. callerid_number:gsub("%D", "")
        end

        -- Filter CallerID name
        callerid_name = callerid_name:gsub("%D", "")

        if (callerid_name == "") then
            callerid_name = callerid_number
        end

        -- Normal call
        if callerid_name == callerid_number then

            table.insert(tmp_xml, [[<action application="set" data="effective_caller_id_number=]]..callerid_number..[["/>]])
            table.insert(tmp_xml, [[<action application="set" data="effective_caller_id_name=]]..callerid_name..[["/>]])
            table.insert(tmp_xml, [[<action application="export" data="nolocal:sip_cid_type=pid"/>]])
            return tmp_xml, tmp_destination_number
        end
        
        -- Check for Anon
        if (callerid_name:find('anon') or callerid_name:find('restricted')) then
            table.insert(tmp_xml, [[<action application="set" data="effective_caller_id_number=anonymous"/>]]);
            table.insert(tmp_xml, [[<action application="set" data="effective_caller_id_name=anonymous"/>]]);
            if (callerid_number ~= "") then
                table.insert(tmp_xml, [[<action application="set" data="sip_h_P-Asserted-Identity=<sip:]]..callerid_number..[[@$${domain}>"/>]])
            end
            table.insert(tmp_xml, [[<action application="set" data="sip_h_Privacy=id;"/>]])
            table.insert(tmp_xml, [[<action application="export" data="nolocal:sip_cid_type=none"/>]])
            return tmp_xml, tmp_destination_number
        end

        -- Check for Forwarded. Name is holding real callee number, number is holding our number
        if (callerid_name:sub(1, 1) == "F" or callerid_name:sub(1, 1) == "D") then
            callerid_name = "+" .. callerid_name:gsub("%D", "")
            table.insert(tmp_xml, [[<action application="set" data="effective_caller_id_number=]]..callerid_name..[["/>]])
            table.insert(tmp_xml, [[<action application="set" data="effective_caller_id_name=]]..callerid_name..[["/>]])
            if (callerid_number ~= "") then
                table.insert(tmp_xml, [[<action application="set" data="sip_h_Diversion=<sip:]]..callerid_number..[[@$${domain}>"/>]])
            end
            return tmp_xml, tmp_destination_number
        end

        callerid_name = "+" .. callerid_name
        
        -- Faking callerID. Assuming real number is number, faking is name
        table.insert(tmp_xml, [[<action application="set" data="effective_caller_id_number=]]..callerid_name..[["/>]])
        table.insert(tmp_xml, [[<action application="set" data="effective_caller_id_name=]]..callerid_name..[["/>]])
        if (callerid_number ~= "") then
            table.insert(tmp_xml, [[<action application="set" data="sip_h_P-Asserted-Identity=<sip:]]..callerid_number..[[@$${domain}>"/>]])
        end
        if (callerid_name ~= "") then
            table.insert(tmp_xml, [[<action application="set" data="sip_h_P-Preferred-Identity=<sip:]]..callerid_name..[[@$${domain}>"/>]])
        end
        table.insert(tmp_xml, [[<action application="export" data="nolocal:sip_cid_type=none"/>]])
    end

    return tmp_xml, tmp_destination_number
end

-- Do number translation OVERRIDE. Use only first occurance
function do_number_translation(number_translation, destination_number)

    local tmp = number_translation:split(',')

    for tmp_key, tmp_value in pairs(tmp) do

        tmp_value = tmp_value:gsub("\"", "")

        if tmp_value:sub(1, 1) == "/" then
            tmp_value = "*" .. tmp_value
        end

        tmp_str = split(tmp_value, "/")

        if(tmp_str[1] == '' or tmp_str[1] == nil) then
            return destination_number
        end

        local prefix = string.sub(destination_number, 0, string.len(tmp_str[1]));

        if (prefix == tmp_str[1] or tmp_str[1] == '*') then
            Logger.notice("[DO_NUMBER_TRANSLATION_OVERRIDE] Before Localization CLI/DST : " .. destination_number)
            if(tmp_str[2] ~= nil) then
                if (tmp_str[2] == '*') then
                    destination_number = string.sub(destination_number, (string.len(tmp_str[1])+1))
                else
                    if (tmp_str[1] == '*') then
                        destination_number = tmp_str[2] .. destination_number
                    else
                        destination_number = tmp_str[2] .. string.sub(destination_number, (string.len(tmp_str[1])+1))
                    end
                end
            else
                destination_number = string.sub(destination_number, (string.len(tmp_str[1])+1))
            end
        Logger.notice("[DO_NUMBER_TRANSLATION_OVERRIDE] After Localization CLI/DST : " .. destination_number)

        return destination_number
        end
    end

    return destination_number
end

-- SIP-DID function call OVERRIDE
function custom_inbound_5(xml, didinfo, userinfo, config, xml_did_rates, callerid_array, livecall_data)

    is_local_extension = "1"
    local bridge_str = ""
    local destination_str = {}
    local common_chan_var = ""
    local deli_str = {}
    local sip_did_backup_info
    local sip_did_backup_string

    local tmp_extensions_list = string.split(didinfo['extensions'], ";")
    local tmp_extensions = tmp_extensions_list[1]

    string.gsub(tmp_extensions, "([^,|]+)", function(value) destination_str[#destination_str + 1] = value end) -- Other form of string:split
    string.gsub(tmp_extensions, "([,|]+)", function(value) deli_str[#deli_str + 1] = value end) -- Other form of string:split

    local destination_number_translated = destination_number

    if (didinfo['prepend_prefix'] and didinfo['prepend_prefix'] ~= "") then
        destination_number_translated = didinfo['prepend_prefix'] .. destination_number_translated
        Logger.notice("[CUSTOM_INBOUND_5_OVERRIDE] Change destination number to " .. destination_number_translated)
    end

    if (didinfo['prepend_suffix'] and didinfo['prepend_suffix'] ~= "") then
        destination_number_translated = destination_number_translated .. didinfo['prepend_suffix']
        Logger.notice("[CUSTOM_INBOUND_5_OVERRIDE] Change destination number to " .. destination_number_translated)
    end    

    table.insert(xml, [[<action application="set" data="calltype=SIP-DID"/>]])

    if (config['opensips'] == '1') then
        common_chan_var = "{sip_contact_user="..destination_number_translated.."}"
        for i = 1, #destination_str do
            if notify then 
                notify(xml,destination_str[i]) 
            end
            bridge_str = bridge_str.."[leg_timeout="..didinfo['leg_timeout'].."]sofia/${sofia_profile_name}/"..destination_number_translated.."${regex(${sofia_contact("..destination_str[i].."@${domain_name})}|^[^@]+(.*)|%1)}"
            if i <= #deli_str then
                bridge_str = bridge_str..deli_str[i]
            end
        end
        
        if (tmp_extensions_list[2]) then -- We have a backup!
            Logger.notice("[CUSTOM_INBOUND_5_OVERRIDE] Adding a backup to " .. tmp_extensions_list[2])
            sip_did_backup_info = string.split(tmp_extensions_list[2], "@") -- Check format like <number@ip> or just <ip>
            if (sip_did_backup_info[2]) then
                sip_did_backup_string = "[leg_timeout="..didinfo['leg_timeout'].."]sofia/${sofia_profile_name}/"..sip_did_backup_info[1].."@"..sip_did_backup_info[2]
            else
                sip_did_backup_string = "[leg_timeout="..didinfo['leg_timeout'].."]sofia/${sofia_profile_name}/"..destination_number_translated.."@"..sip_did_backup_info[1]
            end
            table.insert(xml, [[<action application="set" data="continue_on_fail=NORMAL_TEMPORARY_FAILURE,NO_ROUTE_DESTINATION,USER_NOT_REGISTERED"/>]])
        end

        -- Put first destination
        table.insert(xml, [[<action application="bridge" data="]]..common_chan_var..bridge_str..[["/>]])
        if (sip_did_backup_string) then
            table.insert(xml, [[<action application="bridge" data="]]..sip_did_backup_string..[["/>]])
        end

    else
        common_chan_var = "{sip_invite_params=user=LOCAL,sip_from_uri="..tmp_extensions.."@${domain_name}}"
        for i = 1, #destination_str do
            if notify then notify(xml,destination_str[i]) end
            bridge_str = bridge_str.."[leg_timeout="..didinfo['leg_timeout'].."]sofia/${sofia_profile_name}/"..destination_str[i].."@"..config['opensips_domain']
            if i <= #deli_str then
                bridge_str = bridge_str..deli_str[i]
            end
        end
        table.insert(xml, [[<action application="bridge" data="]]..common_chan_var..bridge_str..[["/>]])
    end
-- To leave voicemail 
    leave_voicemail(xml,destination_number,destination_str[1])
    
    return xml;
end


-- Dialplan for inbound calls
function freeswitch_xml_inbound(xml, didinfo, userinfo, config, xml_did_rates, callerid_array, livecall_data)

    local is_local_extension = "0"
    
    callerid_array['cid_name'] = do_number_translation(didinfo['did_cid_translation'],callerid_array['cid_name'])
    callerid_array['cid_number'] = do_number_translation(didinfo['did_cid_translation'],callerid_array['cid_number'])
    
    if (tonumber(didinfo['maxchannels']) > 0) then    		
        table.insert(xml, [[<action application="limit" data="db ]]..didinfo['accountid']..[[ user_]]..didinfo['accountid']..[[ ]]..didinfo['maxchannels']..[[ !SWITCH_CONGESTION"/>]]);
    end
    
    if (tonumber(userinfo['localization_id']) > 0 and or_localization and or_localization['in_caller_id_originate'] ~= nil) then

        callerid_array['cid_name'] = do_number_translation(or_localization['in_caller_id_originate'], callerid_array['cid_name'])
        callerid_array['cid_number'] = do_number_translation(or_localization['in_caller_id_originate'], callerid_array['cid_number'])
        
    end

    xml = freeswitch_xml_callerid(xml, callerid_array)

    table.insert(xml, [[<action application="set" data="receiver_accid=]]..didinfo['accountid']..[["/>]])
    
    if(tonumber(didinfo['maxchannels']) > 0) then    
        table.insert(xml, [[<action application="limit" data="db ]]..destination_number..[[ did_]]..destination_number..[[ ]]..didinfo['maxchannels']..[[ !SWITCH_CONGESTION"/>]]);        
    end
    
    if callerid_lookup_dialplan then 
        callerid_lookup_dialplan(xml,didinfo) 
    end

    table.insert(xml, [[<action application="export" data="presence_data=]]..livecall_data..[[||||||DID"/>]])
    table.insert(xml, [[<action application="export" data="call_type=]]..didinfo['call_type']..[["/>]])
    
    local custom_function_name = "custom_inbound_"..didinfo['call_type']
    
    Logger.debug("[FREESWITCH_XML_INBOUND_OVERRIDE] Calling " .. custom_function_name)

    if (_G[custom_function_name] ~= nil) then
        _G[custom_function_name](xml, didinfo, userinfo, config, xml_did_rates, callerid_array, livecall_data) -- calls function from the global namespace
    else
        Logger.error("[FREESWITCH_XML_INBOUND_OVERRIDE] Function " .. custom_function_name .. " is not present!")
        table.insert(xml, [[action application="hangup" data="SWITCH_CONGESTION"/>]])
    end
    
    return xml
end


function freeswitch_xml_callerid(xml, calleridinfo)

    Logger.notice("[FREESWITCH_XML_CALLERID_OVERRIDE] Setting CallerID to <" .. (calleridinfo['cid_name'] or "NONE") .. "> " .. (calleridinfo['cid_number'] or "NONE"))

    if (calleridinfo['cid_name'] ~= '' and calleridinfo['cid_name'] ~= '<null>')  then
        table.insert(xml, [[<action application="set" data="effective_caller_id_name=]]..calleridinfo['cid_name']..[["/>]]);
    end
    if (calleridinfo['cid_number'] ~= '' and calleridinfo['cid_number'] ~= '<null>')  then
        table.insert(xml, [[<action application="set" data="effective_caller_id_number=]]..calleridinfo['cid_number']..[["/>]]);
    end

    return xml
end

-- Get carrier rates OVERRIDE
function get_carrier_rates(destination_number, number_loop_str, ratecard_id, rate_carrier_id, routing_type)
    
    local carrier_rates = {}     
    local trunk_id = 0     
    local query
    
    if routing_type == 1 then
        query = "SELECT TK.id as trunk_id,TK.name as trunk_name,TK.codec,GW.name as path,GW.dialplan_variable,TK.provider_id,TR.init_inc,TK.status,TK.maxchannels,TK.cps,TK.leg_timeout,TR.pattern,TR.id as outbound_route_id,TR.connectcost,TR.comment,TR.includedseconds,TR.cost,TR.inc,TR.prepend,TR.strip,(select name from "..TBL_GATEWAYS.." where status=0 AND id = TK.failover_gateway_id) as path1,(select name from "..TBL_GATEWAYS.." where status=0 AND id = TK.failover_gateway_id1) as path2 FROM (select * from "..TBL_TERMINATION_RATES.." order by LENGTH (pattern) DESC) as TR "..TBL_TRUNKS.." as TK,"..TBL_GATEWAYS.." as GW WHERE GW.status=0 AND GW.id= TK.gateway_id AND TK.status=0 AND TK.id= TR.trunk_id AND "..number_loop_str.." AND TR.status = 0 "
    else
        query = "SELECT TK.id as trunk_id,TK.name as trunk_name,TK.codec,GW.name as path,GW.dialplan_variable,TK.provider_id,TR.init_inc,TK.status,TK.maxchannels,TK.cps,TK.leg_timeout,TR.pattern,TR.id as outbound_route_id,TR.connectcost,TR.comment,TR.includedseconds,TR.cost,TR.inc,TR.prepend,TR.strip,(select name from "..TBL_GATEWAYS.." where status=0 AND id = TK.failover_gateway_id) as path1,(select name from "..TBL_GATEWAYS.." where status=0 AND id = TK.failover_gateway_id1) as path2 FROM "..TBL_TERMINATION_RATES.." as TR,"..TBL_TRUNKS.." as TK,"..TBL_GATEWAYS.." as GW WHERE GW.status=0 AND GW.id= TK.gateway_id AND TK.status=0 AND TK.id= TR.trunk_id AND "..number_loop_str.." AND TR.status = 0 "
    end

    if (rate_carrier_id and rate_carrier_id ~= nil and rate_carrier_id ~= '0' and string.len(rate_carrier_id) >= 1) then
        query = query.." AND TR.trunk_id IN ("..rate_carrier_id..") "
    else
        trunk_ids={}
        local query_trunks  = "SELECT GROUP_CONCAT(trunk_id) as ids FROM "..TBL_ROUTING.." WHERE pricelist_id="..ratecard_id.." ORDER by id asc";    

        Logger.notice("[GET_CARRIER_RATES_OVERRIDE] Trunks query :" .. query_trunks)

        assert (dbh:query(query_trunks, function(u)
            trunk_ids = u
        end))

        if (trunk_ids['ids'] == "" or trunk_ids['ids'] == nil) then
            trunk_ids['ids'] = 0
        end

        query = query.." AND TR.trunk_id IN ("..trunk_ids['ids']..")"
    end

    if routing_type == "1" then
        query = query.." ORDER by TR.cost ASC,TR.precedence ASC, TK.precedence"
    else
        query = query.." ORDER by LENGTH (pattern) DESC,TR.cost ASC,TR.precedence ASC, TK.precedence"
    end

    Logger.notice("[GET_CARRIER_RATES_OVERRIDE] Query :" .. query)

    local i = 1
    local carrier_ignore_duplicate = {}
    
    assert (dbh:query(query, function(u)
        if (carrier_ignore_duplicate[u['trunk_id']] == nil) then
            carrier_rates[i] = u
            Logger.notice("[GET_CARRIER_RATES_OVERRIDE] Adding carrier rate " .. i)
            i = i + 1
            carrier_ignore_duplicate[u['trunk_id']] = true
        end
    end))

    -- Get also dialplan variables of failover gateways
    for i, carrier_rate in pairs(carrier_rates) do

        Logger.notice("[GET_CARRIER_RATES_OVERRIDE] Checking for gw variables carrier rate " .. i)

        local gateway_name1 = carrier_rate['path1']
        local gateway_name2 = carrier_rate['path2']

        if (gateway_name1 and #gateway_name1 > 0) then
            query = "SELECT dialplan_variable FROM " ..TBL_GATEWAYS.." WHERE status = 0 AND name = '" .. gateway_name1 .. "' LIMIT 1"
            Logger.notice("[GET_CARRIER_RATES_OVERRIDE] Query for failover gw #1 " .. query)
            assert (dbh:query(query, function(row) 
                carrier_rates[i]['dialplan_variable_1'] = row['dialplan_variable']               
                Logger.notice("[GET_CARRIER_RATES_OVERRIDE] Dialplan variables for failover gw #1 " .. row['dialplan_variable'])
            end))
        end

        if (gateway_name2 and #gateway_name2 > 0) then
            query = "SELECT dialplan_variable FROM " ..TBL_GATEWAYS.." WHERE status = 0 AND name = '" .. gateway_name2 .. "' LIMIT 1"
            Logger.notice("[GET_CARRIER_RATES_OVERRIDE] Query for failover gw #2 " .. query)
            assert (dbh:query(query, function(row) 
                carrier_rates[i]['dialplan_variable_2'] = row['dialplan_variable']
                Logger.notice("[GET_CARRIER_RATES_OVERRIDE] Dialplan variables for failover gw #2 " .. row['dialplan_variable'])
            end))
        end
    end

    return carrier_rates
end

-- Check avilable DID info 
function is_did_orphaned(destination_number, config)

    local did_localization = nil 
    local check_did_info = ""
    local tmp_destination_number = destination_number
    if (config['did_global_translation'] ~= nil and config['did_global_translation'] ~= '' and tonumber(config['did_global_translation']) > 0) then
        did_localization = get_localization(config['did_global_translation'], 'O')
        if (did_localization ~= nil) then
            did_localization['number_originate'] = did_localization['number_originate']:gsub(" ", "")
            tmp_destination_number = do_number_translation(did_localization['number_originate'], destination_number)
        end
    end
    
    local query = "SELECT * FROM "..TBL_DIDS.." WHERE " .. did_fix_query_austrian("number", tmp_destination_number, 5) .. " AND (accountid = 0 OR status = 1) LIMIT 1";
    Logger.debug("[IS_DID_ORPHANED_OVERRIDE] Query :" .. query)
    assert (dbh:query(query, function(u)
        check_did_info = u;	 
    end))
    return check_did_info;
end


function skytel_number_normalization(xml, destination_number, calleridinfo)

    Logger.notice("[SKYTEL_NUMBER_NORMALIZATION]: Start")

    local tmp_xml = xml
    local tmp_destination_number = destination_number:gsub("%D", "")

    -- First - unset all custom vars
    table.insert(tmp_xml, [[<action application="unset" data="sip_h_P-Asserted-Identity"/>]])
    table.insert(tmp_xml, [[<action application="unset" data="sip_h_P-Preferred-Identity"/>]])
    table.insert(tmp_xml, [[<action application="unset" data="sip_h_Privacy"/>]])
    table.insert(tmp_xml, [[<action application="unset" data="sip_h_Diversion"/>]])
    table.insert(tmp_xml, [[<action application="export" data="nolocal:sip_cid_type=none"/>]])


    if (calleridinfo ~= nil) then
        local callerid_name = string.lower(calleridinfo['cid_name']) or ""
        local callerid_number = calleridinfo['cid_number'] or ""

        if (callerid_number ~= "" and callerid_number:find('anon') == nil) then
            callerid_number = callerid_number:gsub("%D", "")
        end

        -- Filter CallerID name
        callerid_name = callerid_name:gsub("%D", "")

        if (callerid_name == "") then
            callerid_name = callerid_number
        end

        -- Normal call
        if callerid_name == callerid_number then

            table.insert(tmp_xml, [[<action application="set" data="effective_caller_id_number=]]..callerid_number..[["/>]])
            table.insert(tmp_xml, [[<action application="set" data="effective_caller_id_name=]]..callerid_name..[["/>]])
            table.insert(tmp_xml, [[<action application="export" data="nolocal:sip_cid_type=none"/>]])
            return tmp_xml, tmp_destination_number
        end
        
        -- Check for Anon
        if (callerid_name:find('anon') or callerid_name:find('restricted')) then
            table.insert(tmp_xml, [[<action application="set" data="effective_caller_id_number=anonymous"/>]]);
            table.insert(tmp_xml, [[<action application="set" data="effective_caller_id_name=anonymous"/>]]);
            if (callerid_number ~= "") then
                table.insert(tmp_xml, [[<action application="set" data="sip_h_P-Asserted-Identity=<sip:]]..callerid_number..[[@$${domain}>"/>]])
            end
            table.insert(tmp_xml, [[<action application="set" data="sip_h_Privacy=id;"/>]])
            table.insert(tmp_xml, [[<action application="export" data="nolocal:sip_cid_type=none"/>]])
            return tmp_xml, tmp_destination_number
        end

        -- Check for Forwarded. Name is holding real callee number, number is holding our number
        if (callerid_name:sub(1, 1) == "F" or callerid_name:sub(1, 1) == "D") then
            callerid_name = callerid_name:gsub("%D", "")
            table.insert(tmp_xml, [[<action application="set" data="effective_caller_id_number=]]..callerid_name..[["/>]])
            table.insert(tmp_xml, [[<action application="set" data="effective_caller_id_name=]]..callerid_name..[["/>]])
            if (callerid_number ~= "") then
                table.insert(tmp_xml, [[<action application="set" data="sip_h_Diversion=<sip:]]..callerid_number..[[@$${domain}>"/>]])
            end
            return tmp_xml, tmp_destination_number
        end
        
        -- Faking callerID. Assuming real number is number, faking is name. Here we should use premium routes
        tmp_destination_number = "3030#" .. tmp_destination_number
        
        table.insert(tmp_xml, [[<action application="set" data="effective_caller_id_number=]]..callerid_name..[["/>]])
        table.insert(tmp_xml, [[<action application="set" data="effective_caller_id_name=]]..callerid_name..[["/>]])
        table.insert(tmp_xml, [[<action application="export" data="nolocal:sip_cid_type=none"/>]])
    end

    return tmp_xml, tmp_destination_number
end