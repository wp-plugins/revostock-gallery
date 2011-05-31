/*
Simple Image Trail script- By JavaScriptKit.com
Visit http://www.javascriptkit.com for this script and more
This notice must stay intact
*/

var offsetfrommouse=[15,15]; //image x,y offsets from cursor position in pixels. Enter 0,0 for no offset
var displayduration=0; //duration in seconds image should remain visible. 0 for always.
var currentimageheight = 270;   // maximum image size.

if (document.getElementById || document.all){
        document.write('<div id="trailimageid" style="height:1px; left:0; top:0; position:absolute; width:286px; z-index:1000;">');
        document.write('</div>');
}

//if (document.getElementById || document.all)
//document.write('<div id="trailimageid" style="position:absolute;visibility:visible;left:0px;top:0px;width:1px;height:1px"><img src="'+trailimage[0]+'" border="0" width="'+trailimage[1]+'px" height="'+trailimage[2]+'px"></div>')

function gettrailobj(){
	if (document.getElementById)
	return document.getElementById("trailimageid").style
	else if (document.all)
	return document.all.trailimagid.style
}

function gettrailobjnostyle(){
	if (document.getElementById)
	return document.getElementById("trailimageid")
	else if (document.all)
	return document.all.trailimagid
}

function truebody(){
	return (!window.opera && document.compatMode && document.compatMode!="BackCompat")? document.documentElement : document.body
}
var playerMouseOffset = 0;

function hidetrail(){
	var foo = gettrailobj();
	if(foo) {
		foo.visibility = "hidden";
	}
	//gettrailobj().visibility="hidden";
	foo = gettrailobjnostyle();
	if(foo) {
		foo.innerHTML = "";
	}
	//gettrailobjnostyle().innerHTML=""
	document.onmousemove=""
	foo = gettrailobj();
	if(foo) {
		foo.left="-500px";
	}
	//gettrailobj().left="-500px"
}

function followmouse(e){
        if (typeof playerMouseOffset == 'undefined' ) {
                playerMouseOffset = 0;
        }

        var xcoord=offsetfrommouse[0]
        var ycoord=offsetfrommouse[1]

        var docwidth=document.all? truebody().scrollLeft+truebody().clientWidth : pageXOffset+window.innerWidth-15
        var docheight=document.all? Math.min(truebody().scrollHeight, truebody().clientHeight) : Math.min(document.body.offsetHeight, window.innerHeight)
        //if (document.all){
        //      gettrailobjnostyle().innerHTML = 'A = ' + truebody().scrollHeight + '<br>B = ' + truebody().clientHeight;
        //} else {
        //      gettrailobjnostyle().innerHTML = 'C = ' + document.body.offsetHeight + '<br>D = ' + window.innerHeight;
        //}

        if (typeof e != "undefined"){
                if (docwidth - e.pageX < 300){
                        xcoord = e.pageX - xcoord - 286; // Move to the left side of the cursor
                } else {
                        xcoord += e.pageX;
                }
                if (docheight - e.pageY < (currentimageheight + 110)){
			// bug fix by Marc Swanson.  Safari would show the popup off the screen on widescreen pages.
			// removed the e.PageY from the subtraction in the calc.
                        //ycoord += e.pageY - Math.max(0,(110 + currentimageheight + e.pageY - docheight - truebody().scrollTop));
                        ycoord += e.pageY - Math.max(0,(110 + currentimageheight - docheight - truebody().scrollTop));
                } else {
                        ycoord += e.pageY;
                }

        } else if (typeof window.event != "undefined"){
                if (docwidth - event.clientX < 300){
                        xcoord = event.clientX + truebody().scrollLeft - xcoord - 286; // Move to the left side of the cursor
                } else {
                        xcoord += truebody().scrollLeft+event.clientX
                }
                if (docheight - event.clientY < (currentimageheight + 110)){
                        ycoord += event.clientY + truebody().scrollTop - Math.max(0,(110 + currentimageheight + event.clientY - docheight));
                } else {
                        ycoord += truebody().scrollTop + event.clientY;
                }
        }

        var docwidth=document.all? truebody().scrollLeft+truebody().clientWidth : pageXOffset+window.innerWidth-15
        var docheight=document.all? Math.max(truebody().scrollHeight, truebody().clientHeight) : Math.max(document.body.offsetHeight, window.innerHeight)
        ycoord -= playerMouseOffset;

        gettrailobj().left=xcoord+"px"
        gettrailobj().top=ycoord+"px"
}

function showhover(url){
	document.onmousemove = followmouse;
	gettrailobjnostyle().innerHTML = '<iframe src="'+url+'" height="245px" width="276px"><p>Retrieving preview</p></iframe>';
	gettrailobj().visibility = "visible";
}

if (displayduration>0)
  setTimeout("hidetrail()", displayduration*1000)
