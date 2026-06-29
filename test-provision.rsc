# StudyRoom Connect full MikroTik provisioning
# Includes agent scripts and all hotspot portal files
:put "Starting StudyRoom provisioning"
/system identity set name="testA"
/ip service set api disabled=no port=8728
:if ([:len [/user find name="billing-api"]] = 0) do={/user add name="billing-api" password="ohoSEotlNc2GBzFVzoBeBBkI2hYHMoJd" group=full comment="StudyRoom billing agent/API"} else={/user set [find name="billing-api"] password="ohoSEotlNc2GBzFVzoBeBBkI2hYHMoJd" group=full comment="StudyRoom billing agent/API"}

# Sync hotspot captive portal files
:do {/file remove [find name="hotspot/login.html"]} on-error={}
:do {/tool fetch mode=http url="http://192.168.1.190/wifi-billing-system/provision/ArSEZg9gnvnA3FCU0btUi7dDaHKAuZsJakoZg3DV2zZid3N9wVIyEhexz0bEpvs0jm1lnfkuJAjs7PEg/hotspot/login.html" dst-path="hotspot/login.html"} on-error={:log warning "StudyRoom failed to fetch login.html"}
:do {/file remove [find name="hotspot/status.html"]} on-error={}
:do {/tool fetch mode=http url="http://192.168.1.190/wifi-billing-system/provision/ArSEZg9gnvnA3FCU0btUi7dDaHKAuZsJakoZg3DV2zZid3N9wVIyEhexz0bEpvs0jm1lnfkuJAjs7PEg/hotspot/status.html" dst-path="hotspot/status.html"} on-error={:log warning "StudyRoom failed to fetch status.html"}
:do {/file remove [find name="hotspot/logout.html"]} on-error={}
:do {/tool fetch mode=http url="http://192.168.1.190/wifi-billing-system/provision/ArSEZg9gnvnA3FCU0btUi7dDaHKAuZsJakoZg3DV2zZid3N9wVIyEhexz0bEpvs0jm1lnfkuJAjs7PEg/hotspot/logout.html" dst-path="hotspot/logout.html"} on-error={:log warning "StudyRoom failed to fetch logout.html"}
:do {/file remove [find name="hotspot/error.html"]} on-error={}
:do {/tool fetch mode=http url="http://192.168.1.190/wifi-billing-system/provision/ArSEZg9gnvnA3FCU0btUi7dDaHKAuZsJakoZg3DV2zZid3N9wVIyEhexz0bEpvs0jm1lnfkuJAjs7PEg/hotspot/error.html" dst-path="hotspot/error.html"} on-error={:log warning "StudyRoom failed to fetch error.html"}
:do {/file remove [find name="hotspot/alogin.html"]} on-error={}
:do {/tool fetch mode=http url="http://192.168.1.190/wifi-billing-system/provision/ArSEZg9gnvnA3FCU0btUi7dDaHKAuZsJakoZg3DV2zZid3N9wVIyEhexz0bEpvs0jm1lnfkuJAjs7PEg/hotspot/alogin.html" dst-path="hotspot/alogin.html"} on-error={:log warning "StudyRoom failed to fetch alogin.html"}
:do {/file remove [find name="hotspot/redirect.html"]} on-error={}
:do {/tool fetch mode=http url="http://192.168.1.190/wifi-billing-system/provision/ArSEZg9gnvnA3FCU0btUi7dDaHKAuZsJakoZg3DV2zZid3N9wVIyEhexz0bEpvs0jm1lnfkuJAjs7PEg/hotspot/redirect.html" dst-path="hotspot/redirect.html"} on-error={:log warning "StudyRoom failed to fetch redirect.html"}
:do {/file remove [find name="hotspot/md5.js"]} on-error={}
:do {/tool fetch mode=http url="http://192.168.1.190/wifi-billing-system/provision/ArSEZg9gnvnA3FCU0btUi7dDaHKAuZsJakoZg3DV2zZid3N9wVIyEhexz0bEpvs0jm1lnfkuJAjs7PEg/hotspot/md5.js" dst-path="hotspot/md5.js"} on-error={:log warning "StudyRoom failed to fetch md5.js"}

# StudyRoom Agent Mode - no MikroTik password required by Laravel
/system scheduler remove [find name="studyroom-heartbeat"]
/system scheduler remove [find name="studyroom-pull-commands"]
/system script remove [find name="studyroom-heartbeat"]
/system script remove [find name="studyroom-pull-commands"]
/system script add name="studyroom-heartbeat" policy=read,write,test source={
    :local cpu [/system resource get cpu-load]
    :local uptime [/system resource get uptime]
    :local hs 0
    :local pp 0
    :do {:set hs [/ip hotspot active print count-only]} on-error={:set hs 0}
    :do {:set pp [/ppp active print count-only]} on-error={:set pp 0}
    :local u ("http://192.168.1.190/wifi-billing-system/router-agent/g6PtM77aWc6dkP7JkFed2EwvnhCdk4ZJsBMaLzC5w2P4yJeHrMnNZIxnWV5j0NSfZi2cFyV13JqNvE9S/heartbeat" . "?cpu=" . $cpu . "&uptime=" . $uptime . "&hotspot_active=" . $hs . "&pppoe_active=" . $pp)
    :do {/tool fetch mode=http url=$u keep-result=no} on-error={:log warning "StudyRoom heartbeat failed"}
}
/system script add name="studyroom-pull-commands" policy=read,write,test,sensitive source={
    :do {/tool fetch mode=http url="http://192.168.1.190/wifi-billing-system/router-agent/g6PtM77aWc6dkP7JkFed2EwvnhCdk4ZJsBMaLzC5w2P4yJeHrMnNZIxnWV5j0NSfZi2cFyV13JqNvE9S/commands" dst-path="studyroom-commands.rsc"} on-error={:log warning "StudyRoom command fetch failed"}
    :delay 1s
    :do {/import studyroom-commands.rsc} on-error={:log warning "StudyRoom command import failed"}
}
/system scheduler add name="studyroom-heartbeat" interval=20s start-time=startup on-event="/system script run studyroom-heartbeat"
/system scheduler add name="studyroom-pull-commands" interval=30s start-time=startup on-event="/system script run studyroom-pull-commands"
:delay 1s
:do {/system script run studyroom-heartbeat} on-error={}
:do {/system script run studyroom-pull-commands} on-error={}
:put "StudyRoom provisioning completed"
