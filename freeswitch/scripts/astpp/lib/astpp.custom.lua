
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