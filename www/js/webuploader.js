var plupload_num = 0;
var uploader_arr = {};
$('.plupload').each(function () {
	plupload_num++;
    var ele = this;
	var eid = 'plupload' + plupload_num;
    var fsize = $(this).data("fsize") ||"5mb";
    var fext = $(this).data("fext") || "jpg,jpeg,gif,png";
    var ftitle = $(this).data("title") || "Image files";
    var fnum = $(this).data("fnum") || 10;
	var qrcode = $(this).data("imgtype") || "";
    
	$(this).before('<div class="imageplus plupload" id="'+eid+'"></div>');
	$('#' + eid).append(this);
    var ccuploader = WebUploader.create({
        // swf文件路径
        swf: global.static_url + '/webuploader/Uploader.swf',
        // 文件接收服务端。
        server: global.sUploadUrl,
        // 选择文件的按钮。可选。
        // 内部根据当前运行是创建，可能是input元素，也可能是flash.
        pick: '#'+eid,
        // 不压缩image, 默认如果是jpeg，文件上传前会压缩一把再上传！
        resize: false,
        // 选完文件后，是否自动上传。
        auto: true,
        fileNumLimit: fnum,
        // fileSingleSizeLimit: fsize,
        // 只允许选择图片文件。
        accept: {
            title: ftitle,
            extensions: fext
        },
		formData: {
			nc: qrcode
		},
        disableGlobalDnd: true,
    });

	uploader_arr[eid] = ccuploader;


    
    // 当有文件被添加进队列的时候
    ccuploader.on('fileQueued', function( file ) {
    	// TODO start
    	//console.log('fileQueued');
    });
    
    // 文件上传过程中创建进度条实时显示。
    ccuploader.on('uploadProgress', function( file, percentage ) {
    	//console.log('uploadProgress');
    });
    
    ccuploader.on('uploadSuccess', function( file, ret ) {
    	//console.log('uploadSuccess');
    	//console.log(ret);
    	//return false;
        if (ret.iError != 0) {
            alert(ret.msg);
	        ccuploader.reset();
            return false;
        };
        var sWH = $(ele).data("wh");
        var ret_iWidth = ret.file.iWidth;
        var ret_iHeight = ret.file.iHeight;
        var ret_iRatio = ret_iWidth / ret_iHeight;
        if (sWH) {
        	var opt = sWH.substr(0, 1);
        	var tmp = sWH.substr(1).split(':');
        	var w = tmp[0];
        	var h = tmp[1];
        	if (w < 10 && h < 10) {
        		if (ret_iRatio != w / h) {
        			alert("您上传的图片尺寸比例不对");
                    return false;
        		}
        	} else {
        		if (opt == '=') {
        			if (ret_iWidth != w || ret_iHeight != h) {
        				alert("请上传宽高等于"+w+"*"+h+"的图片");
        			}
        		} else if (opt == '<') {
        			if (ret_iWidth > w || ret_iHeight > h) {
        				alert("请上传宽高小于"+w+"*"+h+"的图片");
        			}
        		} else if (opt == '>') {
        			if (ret_iWidth < w || ret_iHeight < h) {
        				alert("请上传宽高大于"+w+"*"+h+"的图片");
        			}
        		}
        	}
        }
        
        var file = ret.file.sKey + '.' + ret.file.sExt;
        if ($(ele).data('target')) {
            $($(ele).data('target')).val(file);
        }
        if ($(ele).data('img')) {
            var height = $(ele).data('height') || 0;
            var width = $(ele).data('width') || 0;
            // console.log($(ele).data('img'));
            // console.log(getDFSViewURL(file, width, height));
            $($(ele).data('img')).attr('src', getDFSViewURL(file, width, height));
        }
        if ($(ele).data('callback')) {
            eval($(ele).data('callback') + '(\'' + file + '\',' + ret.file.iWidth + ', ' + ret.file.iHeight + ')');
        }
		var del_img_str = "<img class='del-x' data-eid='"+eid+"' data-key='"+ret.file.sKey+"' src='/front/images/x.png' style='width: 16px;position: relative;top:-120px;left: 124px'/>";
	    $('#' + eid).append(del_img_str);
    });

    ccuploader.on('uploadError', function( file ) {
    	console.log('uploadError');
    });

    ccuploader.on('uploadComplete', function( file ) {
    	console.log('uploadComplete');
    });
});

function getDFSViewURL(p_sFileKey, p_iWidth, p_iHeight, p_sOption, p_biz) {
    if (!p_sFileKey) {
        return '';
    }
    p_iWidth = p_iWidth || 0;
    p_iHeight = p_iHeight || 0;
    p_sOption = p_sOption || '';
    p_biz = p_biz || '';

    var sDfsViewUrl = global.sDfsViewUrl;
    if (p_biz == 'banner') {
        sDfsViewUrl += '/fjbanner';
    }
    var tmp = p_sFileKey.split('.');
    var p_sKey = tmp[0];
    var p_sExt = tmp[1];
    if (0 == p_iWidth && 0 == p_iHeight) {
        return sDfsViewUrl + '/' + p_sKey + '.' + p_sExt;
    } else {
        if ('' == p_sOption) {
            return sDfsViewUrl + '/' + p_sKey + '/' + p_iWidth + 'x' + p_iHeight + '.' + p_sExt;
        } else {
            return sDfsViewUrl + '/' + p_sKey + '/' + p_iWidth + 'x' + p_iHeight + '_' + p_sOption + '.' + p_sExt;
        }
    }
}

$(".del-x").live("click", function(){
    var container = $(this).parent("div.webuploader-container");
	var element = container.find("img.plupload");
	var image_key = $(this).data("key");
	var eid = $(this).data("eid");
	if(confirm("您确定要删除此图片？")){
		var default_img_src = "/front/images/tool/imageplus.png";
    	$($(element).data('img')).attr('src', default_img_src);
		$(this).remove();
		uploader_arr[eid].reset();
		//call server to delete file
	}
});

