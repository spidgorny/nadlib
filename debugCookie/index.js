var buttons = require('sdk/ui/button/toggle');
var tabs = require("sdk/tabs");
var urls = require("sdk/url");
var {Cc, Ci} = require("chrome");

var button;
function main() {
	button = buttons.ToggleButton({
		id: "debug-cookie",
		label: "Toggle Debug Cookie",
		icon: {
			"16": "./switch-off-16.png",
			"32": "./switch-off-32.png",
			"64": "./switch-off-64.png"
		},
		onChange: handleClick
	});

	tabs.on("ready", logURL);
}

function handleClick(state) {
	console.log(state.label +  " checked state: " + state.checked);
	var isDebug = checkCookie(tabs.activeTab.url);

	if (isDebug) {
		updateIcon(0);
		setDebug(0);
		button.checked = false;
	} else {
		updateIcon(1);
		setDebug(1);
		button.checked = true;
	}

	var newDebug = checkCookie(tabs.activeTab.url);
	updateIcon(newDebug);
	button.checked = newDebug;
}

function updateIcon(isDebug) {
	if (isDebug) {
		button.icon = {
			"16": "./switch-on-16.png",
			"32": "./switch-on-32.png",
			"64": "./switch-on-64.png"
		};
		button.badge = 1;
	} else {
		button.icon = {
			"16": "./switch-off-16.png",
			"32": "./switch-off-32.png",
			"64": "./switch-off-64.png"
		};
		button.badge = null;
	}
}

function logURL(tab) {
	console.log(tab.url);
	var isDebug = checkCookie(tab.url);
	updateIcon(isDebug);
}

var getCookieManager = function (hostWithoutWWW) {
	var cookieManager = Cc["@mozilla.org/cookiemanager;1"].getService(Ci.nsICookieManager2);
	return cookieManager;
};

function checkCookie(sUrl) {
	var hostWithoutWWW = getHost(sUrl);

	var cookieManager = getCookieManager(hostWithoutWWW);
	var iterator = cookieManager.getCookiesFromHost(hostWithoutWWW);
	while (iterator.hasMoreElements()) {
		var cookie = iterator.getNext().QueryInterface(Ci.nsICookie2);
		//dump(cookie.host + ";" + cookie.name + "=" + cookie.value + "\n");
		if (cookie.name == 'debug' && cookie.value == 1) {
			return true;
		}
	}

	// var heh = cookieManager.getCookieString(sUrl, null);
	// console.log(heh);

	// let iterator = Services.cookies.getCookiesFromHost(url.host);
	// while (iterator.hasMoreElements()) {
	// 	var cookie = iterator.getNext().QueryInterface(Ci.nsICookie2);
	// 	dump(cookie.host + ";" + cookie.name + "=" + cookie.value + "\n");
	// }
	return false;
}

var getHost = function (sUrl) {
	var url = urls.URL(sUrl);
	var hostWithoutWWW = url.host.replace('www.', '');
	console.log(hostWithoutWWW);
	return hostWithoutWWW;
};

function setDebug(value) {
	var sUrl = tabs.activeTab.url;
	var hostWithoutWWW = getHost(sUrl);

	var cookieManager = getCookieManager(hostWithoutWWW);
	cookieManager.add(
		hostWithoutWWW,
		'/',
		'debug',
		value,
		false,
		false,
		false,
		Date.now()+1000*24*60*60 * 100 //days
	);
}

main();
