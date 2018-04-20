-- Kamailio - equivalent of routing blocks in Lua
--
-- KSR - the new dynamic object exporting Kamailio functions (kemi)
-- sr - the old static object exporting Kamailio functions
--

-- Relevant remarks:
--  * do not execute Lua 'exit' - that will kill Lua interpreter which is
--  embedded in Kamailio, resulting in killing Kamailio
--  * use KSR.x.exit() to trigger the stop of executing the script
--  * KSR.drop() is only marking the SIP message for drop, but doesn't stop
--  the execution of the script. Use KSR.x.exit() after it or KSR.x.drop()
--


-- global variables corresponding to defined values (e.g., flags) in kamailio.cfg
FLT_ACC = 1
FLT_ACCMISSED = 2
FLT_ACCFAILED = 3
FLT_NATS = 5

FLB_NATB = 6
FLB_NATSIPPING = 7

ASTPP_ADDR = 'XXX.XXX.XXX.XXX'
ASTPP_PORT = '5060'

-- SIP request routing
-- equivalent of request_route{}
function ksr_request_route()

    -- Some initial vars to populate
    local packet_info = populate_vars()

    -- Check for spam

    if (spam_check(packet_info)) then
        -- Spam is found
        KSR.x.exit()
    end

    -- per request initial checks
    ksr_route_reqinit(packet_info)

    -- NAT detection
    ksr_route_natdetect(packet_info)

    -- CANCEL processing
    if packet_info['method'] == "CANCEL" then
        if KSR.tm.t_check_trans() > 0 then
            ksr_route_relay(packet_info)
        end
        return 1;
    end

    -- handle requests within SIP dialogs
    ksr_route_withindlg(packet_info)

    -- -- only initial requests (no To tag)

    -- handle retransmissions
    if KSR.tmx.t_precheck_trans()>0 then
        KSR.tm.t_check_trans();
        return 1;
    end
    if KSR.tm.t_check_trans()==0 then return 1 end

    -- authentication
    if (packet_info['source-ip'] ~= ASTPP_ADDR) then
        ksr_route_auth(packet_info)
    end

    -- record routing for dialog forming requests (in case they are routed)
    -- - remove preloaded route headers
    KSR.hdr.remove("Route");
    if string.find("INVITE|SUBSCRIBE", packet_info['method']) then
        KSR.rr.record_route();
    end

    -- account only INVITEs
    if packet_info['method'] == "INVITE" then
        KSR.setflag(FLT_ACC); -- do accounting
    end

    -- dispatch requests to foreign domains
    -- ksr_route_sipout();

    -- -- requests for my local domains

    -- handle registrations
    ksr_route_registrar(packet_info);

    if KSR.pv.is_null("$rU") then
        -- request with no Username in RURI
        KSR.sl.sl_send_reply(484, "Address Incomplete")
        return 1
    end

    -- Send calls to ASTPP
    if (packet_info['source-ip'] ~= ASTPP_ADDR) then
        KSR.pv.sets("$du", "sip:" .. ASTPP_ADDR.. ":" .. ASTPP_PORT)
        KSR.hdr.append("X-AUTH-IP: " .. packet_info['source-ip'] .. "\r\n")
        ksr_route_relay(packet_info)
    else
        -- Calls from ASTPP
        if (KSR.is_myself(packet_info['request-uri'])) then
            ksr_route_location(packet_info)
        end
        ksr_route_relay(packet_info)
    end
    return 1
end

-- wrapper around tm relay function
function ksr_route_relay(packet_info)
    -- enable additional event routes for forwarded requests
    -- - serial forking, RTP relaying handling, a.s.o.
    if string.find("INVITE,BYE,SUBSCRIBE,UPDATE", packet_info['method']) then
        if KSR.tm.t_is_set("branch_route") < 0 then
            KSR.tm.t_on_branch("ksr_branch_manage");
        end
    end
    if string.find("INVITE,SUBSCRIBE,UPDATE", packet_info['method']) then
        if KSR.tm.t_is_set("onreply_route") < 0 then
            KSR.tm.t_on_reply("ksr_onreply_manage");
        end
    end

    if packet_info['method'] == "INVITE" then
        if KSR.tm.t_is_set("failure_route") < 0 then
            KSR.tm.t_on_failure("ksr_failure_manage");
        end
    end

    if KSR.tm.t_relay() < 0 then
        KSR.sl.sl_reply_error();
    end
    KSR.x.exit();
end


-- Per SIP request initial checks
function ksr_route_reqinit(packet_info)
    if not KSR.is_myself(packet_info['source-ip']) then
        if not KSR.pv.is_null("$sht(ipban=>$si)") then
            -- ip is already blocked
            KSR.dbg("request from blocked IP - " .. packet_info['method']
                    .. " from " .. packet_info['from-uri'] .. " (IP:"
                    .. packet_info['source-ip'] .. ":" .. packet_info['source-port'] .. ")\n");
            KSR.x.exit();
        end
        if KSR.pike.pike_check_req()<0 then
            KSR.err("ALERT: pike blocking " .. packet_info['method']
                    .. " from " .. packet_info['from-uri'] .. " (IP:"
                    .. packet_info['source-ip'] .. ":" .. packet_info['source-port'] .. ")\n");
            KSR.pv.seti("$sht(ipban=>$si)", 1);
            KSR.x.exit();
        end
    end

    if KSR.maxfwd.process_maxfwd(10) < 0 then
        KSR.sl.sl_send_reply(483,"Too Many Hops");
        KSR.x.exit();
    end

    if packet_info['method'] == "OPTIONS"
            and KSR.is_myself(packet_info['request-uri'])
            and KSR.pv.is_null("$rU") then
        KSR.sl.sl_send_reply(200,"Keepalive");
        KSR.x.exit();
    end

    if KSR.sanity.sanity_check(1511, 7)<0 then
        KSR.err("Malformed SIP message from "
                .. packet_info['source-ip'] .. ":" .. packet_info['source-port'] .."\n");
        KSR.x.exit();
    end

end


-- Handle requests within SIP dialogs
function ksr_route_withindlg(packet_info)
    if KSR.siputils.has_totag() < 0 then 
        return 1
    end

    -- sequential request withing a dialog should
    -- take the path determined by record-routing
    if KSR.rr.loose_route() > 0 then
        ksr_route_dlguri()
        if packet_info['method'] =="BYE" then
            KSR.setflag(FLT_ACC) -- do accounting ...
            KSR.setflag(FLT_ACCFAILED) -- ... even if the transaction fails
        elseif packet_info['method'] == "ACK" then
            -- ACK is forwarded statelessly
            ksr_route_natmanage()
        elseif packet_info['method'] == "NOTIFY" then
            -- Add Record-Route for in-dialog NOTIFY as per RFC 6665.
            KSR.rr.record_route()
        end
        ksr_route_relay(packet_info)
        KSR.x.exit()
    end
    if packet_info['method'] == "ACK" then
        if KSR.tm.t_check_trans() > 0 then
            -- no loose-route, but stateful ACK;
            -- must be an ACK after a 487
            -- or e.g. 404 from upstream server
            ksr_route_relay(packet_info)
            KSR.x.exit()
        else
            -- ACK without matching transaction ... ignore and discard
            KSR.x.exit()
        end
    end
    KSR.sl.sl_send_reply(404, "Not here")
    KSR.x.exit()
end

-- Handle SIP registrations
function ksr_route_registrar(packet_info)
    if packet_info['method'] ~= "REGISTER" then 
        return 1 
    end

    if KSR.isflagset(FLT_NATS) then
        KSR.setbflag(FLB_NATB)
        -- do SIP NAT pinging
        KSR.setbflag(FLB_NATSIPPING)
    end

    if KSR.registrar.save("location", 0) < 0 then
        KSR.sl.sl_reply_error();
    end
    KSR.x.exit();
end

-- User location service
function ksr_route_location(packet_info)
    local rc = KSR.registrar.lookup("location");
    if rc < 0 then
        KSR.tm.t_newtran();
        if rc==-1 or rc==-3 then
            KSR.sl.send_reply("404", "Not Found");
            KSR.x.exit();
        elseif rc==-2 then
            KSR.sl.send_reply("405", "Method Not Allowed");
            KSR.x.exit();
        end
    end

    -- when routing via usrloc, log the missed calls also
    if packet_info['method'] == "INVITE" then
        KSR.setflag(FLT_ACCMISSED);
    end

    ksr_route_relay(packet_info)
    KSR.x.exit()
end


-- IP authorization and user uthentication
function ksr_route_auth(packet_info)

    if packet_info['method'] ~= "REGISTER" then
        if KSR.permissions.allow_source_address(1) > 0 then
            -- source IP allowed
            return 1;
        end
    end

    if packet_info['method'] == "REGISTER" or KSR.is_myself(packet_info['from-uri']) then
        -- authenticate requests
        if KSR.auth_db.auth_check(packet_info['from-domain'], "subscriber", 1) < 0 then
            KSR.auth.auth_challenge(packet_info['from-domain'], 0);
            KSR.x.exit();
        end
        -- user authenticated - remove auth header
        if not string.find("REGISTER,PUBLISH", packet_info['method']) then
            KSR.auth.consume_credentials();

            -- Get accountcode for this user
            if packet_info['method'] == "INVITE" then
                local sql = "SELECT accountcode, effective_caller_id_name, effective_caller_id_number, status FROM subscriber WHERE username = \"".. packet_info['from-username'] .. "\" LIMIT 1"
                local account_info = {}
                
                KSR.sqlops.sql_query("local", sql, "sql_res")
                -- Our result should be at 0, 0 position if any
                for i = 0, 3 do
                    if KSR.sqlops.sql_is_null("sql_res", 0, i) ~= 1 then
                        account_info[i] = KSR.pv.get("$dbr(sql_res=>[0, ".. i .. "])")
                    else 
                        account_info[i] = ""
                    end
                end
                KSR.sqlops.sql_result_free("sql_res")

                -- Check if account enabled
                if (account_info[3] == 1) then
                    KSR.sl.send_reply(403, "Account Disabled")
                    KSR.x.exit()
                end

                KSR.hdr.append("X-AUTH: true\r\n")
                KSR.hdr.append("P-Accountcode: ".. account_info[0] .."\r\n")
                KSR.hdr.append("P-CallerID-name: ".. account_info[1] .."\r\n")
                KSR.hdr.append("P-CallerID-number: ".. account_info[2] .."\r\n")

            end

        end
    end

    -- if caller is not local subscriber, then check if it calls
    -- a local destination, otherwise deny, not an open relay here
    if (not KSR.is_myself(packet_info['from-uri'])
            and (not KSR.is_myself(packet_info['request-uri']))) then
        KSR.sl.sl_send_reply(403,"Not relaying");
        KSR.x.exit();
    end

    return 1;
end

-- Caller NAT detection
function ksr_route_natdetect(packet_info)

    KSR.force_rport()
    if KSR.nathelper.nat_uac_test(23)>0 then
        KSR.nathelper.fix_nated_contact()

        if KSR.pv.get("$rm")=="REGISTER" then
            KSR.nathelper.fix_nated_register()
        elseif KSR.siputils.is_first_hop()>0 then
            KSR.nathelper.set_contact_alias()
        end

        KSR.setflag(FLT_NATS)
    end
    return 1
end

-- RTPEngine control
function ksr_route_natmanage(packet_info)
    if KSR.siputils.is_request()>0 then
        if KSR.siputils.has_totag()>0 then
            if KSR.rr.check_route_param("nat=yes")>0 then
                KSR.setbflag(FLB_NATB);
            end
        end
    end
    if (not (KSR.isflagset(FLT_NATS) or KSR.isbflagset(FLB_NATB))) then
        return 1;
    end

    --KSR.rtpengine.rtpengine_manage("replace-origin replace-session-connection");

    if KSR.siputils.is_request()>0 then
        if not KSR.siputils.has_totag() then
            if KSR.tmx.t_is_branch_route()>0 then
                KSR.rr.add_rr_param(";nat=yes");
            end
        end
    end
    if KSR.siputils.is_reply()>0 then
        if KSR.isbflagset(FLB_NATB) then
            KSR.nathelper.set_contact_alias();
        end
    end
    return 1;
end

-- URI update for dialog requests
function ksr_route_dlguri()
    if not KSR.isdsturiset() then
        KSR.nathelper.handle_ruri_alias();
    end
    return 1;
end

-- Manage outgoing branches
-- equivalent of branch_route[...]{}
function ksr_branch_manage()
    local packet_info = populate_vars()
    ksr_route_natmanage(populate_vars)
    return 1
end

-- Manage incoming replies
-- equivalent of onreply_route[...]{}
function ksr_onreply_manage()

    local packet_info = populate_vars()
    KSR.dbg("incoming reply\n");
    packet_info['reply-code'] = KSR.pv.get("$rs");
    if packet_info['reply-code'] > 100 and packet_info['reply-code'] < 299 then
        ksr_route_natmanage(packet_info);
    end
    return 1;
end

-- Manage failure routing cases
-- equivalent of failure_route[...]{}
function ksr_failure_manage()

    local packet_info = populate_vars()
    ksr_route_natmanage(packet_info)

    if KSR.tm.t_is_canceled()>0 then
        return 1;
    end
    return 1;
end

-- SIP response handling
-- equivalent of reply_route{}
function ksr_reply_route()
    -- KSR.info("===== response - from kamailio lua script\n");
    return 1;
end

function spam_check(packet_info)

    -- Most obviuos check
    if packet_info['contact']:find("1.1.1.1") or packet_info['from-uri']:find("1.1.1.1") then
        KSR.info("[SPAM_CHECK] 1.1.1.1 found in Contact or From: "..packet_info['contact'].."/"..packet_info['from-uri'])     
        return true
    end

    local spam_pattern = {"friendly-scanner", "sipvicious", "sipcli", "vaxasip", "sip-scan", "iWar", "sipsak"}
    
    if packet_info['user-agent'] and find_in_pattern(packet_info['user-agent'], spam_pattern) then
        KSR.info("[SPAM_CHECK] Spam found for user agent: "..packet_info['user-agent'])        
        return true
    end

    
    if (packet_info['body'] and KSR.textops.has_body_type("application/sdp") ~= -1) then
        if find_in_pattern(packet_info['body'], spam_pattern) then
            KSR.info("[SPAM_CHECK] Spam found for body: " .. packet_info['body'])   
            return true
        end
    end

    return false
end

function find_in_pattern(needle, haystack)
    if (needle == nil or haystack == nil) then
        return false
    end

    if (type(haystack) ~= "table" and type(needle) ~= "string") then
        return false
    end

    for _, v in pairs(haystack) do
        if (v and type(v) == "string" and string.find(needle, v)) then
            return true
        end
    end
    return false
end

function populate_vars()
    local packet = {}

    packet['method'] = KSR.pv.get("$rm")
    packet['user-agent'] = KSR.pv.get("$ua")
    packet['from-uri'] = KSR.pv.get("$fu")
    packet['from-domain'] = KSR.pv.get("$fd")
    packet['from-username'] = KSR.pv.get("$fU")
    packet['contact'] = KSR.pv.get("$ct") or ""
    packet['user-agent'] = (not KSR.pv.is_null("$ua")) and KSR.pv.get("$ua") or nil
    packet['source-ip'] = KSR.pv.get("$si")
    packet['source-port'] = KSR.pv.get("$sp")
    packet['request-uri'] = KSR.pv.get("$ru")
    packet['request-uri-username'] = KSR.pv.get("$rU")
    packet['body'] = (not KSR.pv.is_null("$rb")) and KSR.pv.get("$rb") or nil

    return packet
end