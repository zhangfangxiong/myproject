var phonezoom =640;
var phoneWidth = parseInt(window.screen.width);
var phoneScale = phoneWidth/phonezoom;
var ua = navigator.userAgent;
//alert(ua)
if (/Android (\d+\.\d+)/.test(ua)){
	var version = parseFloat(RegExp.$1);
	// andriod 2.3
	if(version>2.3){
		document.write('<meta name="viewport" content="width=640, minimum-scale = '+phoneScale+', maximum-scale = '+phoneScale+', target-densitydpi=device-dpi">');
		//alert(phoneScale);
	// andriod 2.3以上
	}else{
		document.write('<meta name="viewport" content="width=640, minimum-scale = '+phoneScale+', maximum-scale = '+phoneScale+',target-densitydpi=device-dpi">');
		//alert(phoneScale);
	}
// 其他系统
}else if(ua.toLowerCase().match(/iPad/i)=="ipad"){
	document.write('<meta name="viewport" content="width=1024, user-scalable=no, target-densitydpi=device-dpi">');
}else if(ua.toLowerCase().match(/iphone os/i) == "iphone os"){
	document.write('<meta name="viewport" content="width=640, user-scalable=no,minimum-scale = '+phoneScale+', maximum-scale = '+phoneScale+'">');
}else{
	document.write('<meta name="viewport" content="width=device-width,target-densitydpi=high-dpi,initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no"/>');
	}