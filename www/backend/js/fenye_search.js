var commonObj = {
    ajaxUrl: "", //模糊搜索url
    moreUrl: "", //下拉分页更多的url
    searchUrl: "",
    ajaxstatus: true,
    fenyestatus: true,
    pageData: {}, //下拉分页传输的data
    appendId: "", //下拉分页append的父级ID
    mohuSearch: function () {
        var _this = this;
        if (document.getElementById('m_searchinput')) {
            //搜索匹配
            document.getElementById('m_searchinput').addEventListener('input', function (e) {
                var keyword = e.target.value;
                var searchResult = [];
                if ($.trim(keyword) != "") {
                    $.ajax({
                        url: _this.ajaxUrl,
                        type: "post",
                        data: {keyword: keyword},
                        dataType: "json",
                        success: function (res) {
                            var dataLength = res.data.data.length;
                            if (dataLength > 0) {
                                for (var i = 0; i < dataLength; i++) {
                                    searchResult[i] = '<a class="m_searchli" href="' + res.data.data[i].searchUrl + '?keyword=' + res.data.data[i].keyword + '">'+
                                         '<div class="m_searchinner">' + res.data.data[i].keyword + '</div>'+
                                       '</a>';
                                }
                                $("#showmohulist").html(searchResult.join("")).show();
                            } else {
                                $("#showmohulist").hide();
                            }
                        }
                    })
                } else {
                    $("#showmohulist").html("").show();
                }
            });
        }
    },
    scrollNextPage: function () {
        var _this = this;
        // $(window).scroll(_this.scrollFn);
        $(window).on("touchmove", _this.scrollFn);

    },
    scrollFn: function () {
        var clientHeight = $(window).height();
        var wholeHeight = $(document).height();
        var scrollTopHeight = $(window).scrollTop();
        if (clientHeight + scrollTopHeight + 50 >= wholeHeight && commonObj.ajaxstatus) {
            if (commonObj.fenyestatus) {
                commonObj.ajaxstatus = false;
                $(".loaddiv").show();
                $.ajax({
                    url: commonObj.moreUrl,
                    type: "post",
                    dataType: "json",
                    data: commonObj.pageData,
                    success: function (res) {
                        commonObj.searchCallBack(res);
                    },
                    error:function(res){
                        console.log(res);

                    }
                })
            } else {
                return
            }
        }
    },
    searchCallBack: function (obj) {
    }
}





