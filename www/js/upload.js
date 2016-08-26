$('.plupload').each(function () {
    var ele = this;
    var fsize = $(ele).data("fsize") ||"2mb";
    var fext = $(ele).data("ext") || "jpg,jpeg,gif,png,xls,xlsx";
    var ftitle = $(ele).data("title") || "Image files";
    var fWidth = $(ele).data("twidth") || 0;
    var fHeight = $(ele).data("theight") || 0;
    var fBili = $(ele).data("bili") || 0;
    var ccuploader = new plupload.Uploader({
        runtimes: 'html5,flash,silverlight,html4',
        browse_button: ele,
        url: global.sUploadUrl,
        flash_swf_url: global.static_url + '/plupload/Moxie.swf',
        silverlight_xap_url: global.static_url + '/plupload/Moxie.xap',
        filters: {
            max_file_size: fsize,
            mime_types: [
                {title: ftitle, extensions: fext}
            ],
            prevent_duplicates: true
        },
        init: {
            FilesAdded: function (up, files) {
                ccuploader.start();
            },
            FileUploaded: function (up, file, ret) {
                eval('var tmp=' + ret.response);
                console.log(tmp);
                if (tmp.iError != 0) {
                    alert(tmp.msg);
                    return true;
                };
                var ret_iWidth = tmp.file.iWidth;
                var ret_iHeight = tmp.file.iHeight;
                var ret_iBili = ret_iWidth / ret_iHeight;
                // 宽度检测
                if (fWidth != 0) {
            		var iWidth = parseInt(fWidth.replace(/[>=<]/g, ''), 10);
                	if (fWidth[0] == '>') {
                		if (ret_iWidth <= iWidth) {
                			alert("请上传宽度大于"+iWidth+"的图片");
                            return false;
                		}
                	} else if (fWidth[0] == '<') {
                		if (ret_iWidth >= iWidth) {
                			alert("请上传宽度小于"+iWidth+"的图片");
                            return false;
                		}
                	} else {
                		if (ret_iWidth != iWidth) {
                			alert("请上传宽度等于"+iWidth+"的图片");
                            return false;
                		}
                	}
                }
                
                // 高度检测
                if (fHeight != 0) {
            		var iHeight = parseInt(fHeight.replace(/[>=<]/g, ''), 10);
                	if (fHeight[0] == '>') {
                		if (ret_iHeight <= iHeight) {
                			alert("请上传高度大于"+iHeight+"的图片");
                            return false;
                		}
                	} else if (fHeight[0] == '<') {
                		if (ret_iHeight >= iHeight) {
                			alert("请上传高度小于"+iHeight+"的图片");
                            return false;
                		}
                	} else {
                		if (ret_iHeight != iHeight) {
                			alert("请上传高度等于"+iHeight+"的图片");
                            return false;
                		}
                	}
                }
                
                // 比例检测
                if (fBili != 0) {
                	var b = fBili.split(':');
                	if (ret_iBili != b[0]/b[1]) {
                		alert("您上传的图片尺寸比例为" + fBili + "的图片");
                        return false;
                	}
                }
                
                
                var file = tmp.file.sKey + '.' + tmp.file.sExt;
                if ($(ele).data('target')) {
                    $($(ele).data('target')).val(file);
                }
                if ($(ele).data('img')) {
                    var height = $(ele).data('height') || 0;
                    var width = $(ele).data('width') || 0;
                    $($(ele).data('img')).attr('src', getDFSViewURL(file, width, height));
                }
                if ($(ele).data('callback')) {
                    eval($(ele).data('callback') + '(tmp.file)');
                }
            },
            Error: function (up, err) {
                alert(err.message);
            }
        }
    });
    ccuploader.init();
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