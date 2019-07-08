/// <reference path="../../../../vendor/yankee42/typescript-server-sent-events/sse.d.ts" />
function addEvent(html_element, event_name, event_function) {
    if (html_element.addEventListener) { // Modern
        html_element.addEventListener(event_name, event_function, false);
    }
    else if (html_element.attachEvent) { // Internet Explorer
        html_element.attachEvent("on" + event_name, event_function);
    }
    else { // others
        html_element["on" + event_name] = event_function;
    }
}
function startTask(url, target) {
    /* create the event source */
    var source = new EventSource(url);
    /* handle incoming messages */
    source.onmessage = function (event) {
        //console.log(event);
        if (event.type == 'message') {
            // data expected to be in JSON-format, so parse */
            var data = JSON.parse(event.data);
            //console.log(event.data.length, data);
            // server sends complete:true on completion
            if (data.complete) {
                // close the connection so browser does not keep connecting
                source.close();
                // update the UI now that task is complete
                target.innerHTML = data.complete;
            }
            // otherwise, it's a progress update so just update progress bar
            else {
                var pct = 100.0 * data.current / data.total;
                //console.log(pct);
                document.getElementById('progress-bar').style.width = pct + '%';
                document.getElementById('pb_text').innerHTML =
                    Math.round(pct) + '% (' + data.current + ' of ' + data.total + ')';
            }
        }
    };
    source.onerror = function (event) {
        var txt;
        var xhr = event.target;
        switch (xhr.readyState) {
            // if reconnecting
            case EventSource.CONNECTING:
                txt = 'Reconnecting...';
                break;
            // if error was fatal
            case EventSource.CLOSED:
                txt = 'Connection failed. Will not retry.';
                break;
        }
        console.log(txt);
        source.close();
    };
}
addEvent(document, 'DOMContentLoaded', function () {
    //console.log('domready');
    var target = document.getElementById('sseTarget');
    var href = target.getAttribute('href');
    startTask(href, target);
});
