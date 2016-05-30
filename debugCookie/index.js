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
	console.log('isDebug: ', isDebug);

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
	console.log('newDebug: ', newDebug);
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

var getCookieManager = function () {
	var cookieManager = Cc["@mozilla.org/cookiemanager;1"].getService(Ci.nsICookieManager2);
	return cookieManager;
};

function checkCookie(sUrl) {
	var hostWithoutWWW = getHost(sUrl);

	var cookieManager = getCookieManager();
	var iterator = cookieManager.getCookiesFromHost(hostWithoutWWW);
	while (iterator.hasMoreElements()) {
		var cookie = iterator.getNext().QueryInterface(Ci.nsICookie2);
		dump(cookie.host + ";" + cookie.name + "=" + cookie.value + "\n");
		dump(cookie);
		if (cookie.name == 'debug' && cookie.value == "1") {
			console.log('Debug cookie found');
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
	if (url.host) {
		var hostWithoutWWW = url.host.replace('www.', '');
		console.log('hostWithoutWWW: ', hostWithoutWWW);
		return hostWithoutWWW;
	} else {
		return null;
	}
};

function setDebug(value) {
	var sUrl = tabs.activeTab.url;
	var hostWithoutWWW = getHost(sUrl);

	if (hostWithoutWWW) {
		var expiresSeconds = new Date().getTime() + 1000 * 24 * 60 * 60 * 100; //days
		var expires = new Date();
		expires.setTime(expiresSeconds);

		if (false) {
			var cookieManager = getCookieManager();
			cookieManager.add(
			'.' + hostWithoutWWW,
			'/',
			'debug',
			value,
			false,
			false,
			false,
			expires
			);
		} else {
			var cookieSvc = Cc["@mozilla.org/cookieService;1"].getService(Ci.nsICookieService);
			var cookieConfig = "debug=" + value;
			cookieConfig += ";domain=." + hostWithoutWWW;
			cookieConfig += ";expires=" + expires.toUTCString();
			console.log(cookieConfig);

			var ios = Cc["@mozilla.org/network/io-service;1"].getService(Ci.nsIIOService);
			var cookieUri = ios.newURI("http://"+hostWithoutWWW, null, null);
			cookieSvc.setCookieString(cookieUri, null, cookieConfig, null);
		}
	} else {
		console.log('Strange url: ', sUrl);
	}
}

main();
//tabs.open('http://google.de/');
//setDebug(1);
