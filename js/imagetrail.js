/*
 Simple Image Trail script- By JavaScriptKit.com
 Visit http://www.javascriptkit.com for this script and more
 This notice must stay intact
 */
var BrowserDetect = {
	init:function () {
		this.browser = this.searchString(this.dataBrowser) || "An unknown browser";
		this.version = this.searchVersion(navigator.userAgent)
				|| this.searchVersion(navigator.appVersion)
				|| "an unknown version";
		this.OS = this.searchString(this.dataOS) || "an unknown OS";
	},
	searchString:function (data) {
		for (var i = 0; i < data.length; i++) {
			var dataString = data[i].string;
			var dataProp = data[i].prop;
			this.versionSearchString = data[i].versionSearch || data[i].identity;
			if (dataString) {
				if (dataString.indexOf(data[i].subString) != -1)
					return data[i].identity;
			}
			else if (dataProp)
				return data[i].identity;
		}
	},
	searchVersion:function (dataString) {
		var index = dataString.indexOf(this.versionSearchString);
		if (index == -1) return;
		return parseFloat(dataString.substring(index + this.versionSearchString.length + 1));
	},
	dataBrowser:[
		{
			string:navigator.userAgent,
			subString:"Chrome",
			identity:"Chrome"
		},
		{	 string:navigator.userAgent,
			subString:"OmniWeb",
			versionSearch:"OmniWeb/",
			identity:"OmniWeb"
		},
		{
			string:navigator.vendor,
			subString:"Apple",
			identity:"Safari",
			versionSearch:"Version"
		},
		{
			prop:window.opera,
			identity:"Opera",
			versionSearch:"Version"
		},
		{
			string:navigator.vendor,
			subString:"iCab",
			identity:"iCab"
		},
		{
			string:navigator.vendor,
			subString:"KDE",
			identity:"Konqueror"
		},
		{
			string:navigator.userAgent,
			subString:"Firefox",
			identity:"Firefox"
		},
		{
			string:navigator.vendor,
			subString:"Camino",
			identity:"Camino"
		},
		{		// for newer Netscapes (6+)
			string:navigator.userAgent,
			subString:"Netscape",
			identity:"Netscape"
		},
		{
			string:navigator.userAgent,
			subString:"MSIE",
			identity:"Explorer",
			versionSearch:"MSIE"
		},
		{
			string:navigator.userAgent,
			subString:"Gecko",
			identity:"Mozilla",
			versionSearch:"rv"
		},
		{		 // for older Netscapes (4-)
			string:navigator.userAgent,
			subString:"Mozilla",
			identity:"Netscape",
			versionSearch:"Mozilla"
		}
	],
	dataOS:[
		{
			string:navigator.platform,
			subString:"Win",
			identity:"Windows"
		},
		{
			string:navigator.platform,
			subString:"Mac",
			identity:"Mac"
		},
		{
			string:navigator.userAgent,
			subString:"iPhone",
			identity:"iPhone/iPod"
		},
		{
			string:navigator.platform,
			subString:"Linux",
			identity:"Linux"
		}
	]
};
BrowserDetect.init();
var offsetfrommouse = [50,15]; //image x,y offsets from cursor position in pixels. Enter 0,0 for no offset
var displayduration = 0; //duration in seconds image should remain visible. 0 for always.
var currentimageheight = 270;   // maximum image size.
if (document.getElementById || document.all) {
	document.write('<div id="trailimageid" style="height:1px; left:0; top:0; position:absolute; width:auto; z-index:1000;">');
	document.write('</div>');
}
function gettrailobj() {
	var style = null;
	if (document.getElementById) {
		style = document.getElementById("trailimageid").style;
	} else if (document.all) {
		style = document.all.trailimagid.style;
	}
	return style;
}
function gettrailobjnostyle() {
	var imageid = null;
	if (document.getElementById) {
		imageid = document.getElementById("trailimageid");
	} else if (document.all) {
		imageid =document.all.trailimagid;
	}
	return imageid;
}
function truebody() {
	return (!window.opera && document.compatMode && document.compatMode != "BackCompat") ? document.documentElement : document.body;
}
var playerMouseOffset = 0;
function hidetrail() {
	var foo = gettrailobj();
	if (foo) {
		foo.visibility = "hidden";
	}
	foo = gettrailobjnostyle();
	if (foo) {
		foo.innerHTML = "";
	}
	document.onmousemove = "";
	foo = gettrailobj();
	if (foo) {
		foo.left = "-500px";
	}
}
function followmouse(e) {
	var currentimageheight = 270;   // maximum image size.
	if (typeof playerMouseOffset == 'undefined') {
		playerMouseOffset = 0;
	}
	var xcoord = offsetfrommouse[0]
	var ycoord = offsetfrommouse[1]
	var docwidth = document.all ? truebody().scrollLeft + truebody().clientWidth : pageXOffset + window.innerWidth - 15
	var docheight = document.all ? Math.min(truebody().scrollHeight,truebody().clientHeight) : Math.min(document.body.offsetHeight,window.innerHeight)
	if (typeof e != "undefined") {
		if (docwidth - e.pageX < (500 + offsetfrommouse[0])) {
			xcoord = e.pageX - xcoord - 500; // Move to the left side of the cursor
		} else {
			xcoord += e.pageX;
		}
		if (docheight - e.pageY < (currentimageheight + 110)) {
			// bug fix by Marc Swanson.  Safari would show the popup off the screen on widescreen pages.
			// removed the e.PageY from the subtraction in the calc.
			if (BrowserDetect.browser != 'Safari' && BrowserDetect.browser != 'Chrome') {
				ycoord += e.pageY - Math.max(0,(110 + currentimageheight + e.pageY - docheight - truebody().scrollTop));
			} else {
				ycoord += e.pageY - 300 - Math.max(0,(110 + currentimageheight - docheight - truebody().scrollTop));
				if (ycoord < truebody().scrollTop) {
					ycoord += 300;
				}
			}
		} else {
			ycoord += e.pageY;
		}
	} else if (typeof window.event != "undefined") {
		if (docwidth - event.clientX < 300) {
			xcoord = event.clientX + truebody().scrollLeft - xcoord - 300; // Move to the left side of the cursor
		} else {
			xcoord += truebody().scrollLeft + event.clientX;
		}
		if (docheight - event.clientY < (currentimageheight + 110)) {
			ycoord += event.clientY + truebody().scrollTop - Math.max(0,(110 + currentimageheight + event.clientY - docheight));
		} else {
			ycoord += truebody().scrollTop + event.clientY;
		}
	}
	docwidth = document.all ? truebody().scrollLeft + truebody().clientWidth : pageXOffset + window.innerWidth - 15;
	docheight = document.all ? Math.max(truebody().scrollHeight,truebody().clientHeight) : Math.max(document.body.offsetHeight,window.innerHeight);
	ycoord -= playerMouseOffset;
	gettrailobj().left = xcoord + "px";
	gettrailobj().top = ycoord + "px";
}
function showhover(url,FileType,FormatID,loader) {
	if (FileType == 'ae' || FileType == 'motion') {
		width = 600;
		height = 600;
	}
	else if (FileType == 'video') {
		width = 600;
		height = 600;
	} else {
		width = 600;
		height = 600;
	}
	document.onmousemove = followmouse;
	gettrailobjnostyle().innerHTML = '<iframe ALLOWTRANSPARENCY="true" class="popup" frameborder="0" style="background-image: url(\'' + loader + '\');" src="' + url + '" height="' + height + 'px" width="' + width + 'px"><p>Retrieving preview</p></iframe>';
	gettrailobj().visibility = "visible";
}
if (displayduration > 0)
	setTimeout("hidetrail()",displayduration * 1000);
