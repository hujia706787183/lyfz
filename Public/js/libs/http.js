var http = (function () {
    http = {
        request: function(settings){
            var def = $.Deferred();
            settings.dataType = "JSON";
            $.ajax(settings).then(function (response){
                if (!response.code){
                    def.resolve(response.data);
                } else if (response.code > 0) {
                    this.onWarning(response.info);
                    def.resolve(response.data);
                } else {
                    def.reject(response.info);
                }
            }.bind(this))

            return def.fail(this.onError);
        },
        get : function(url, data){
            var settings = {
                url: url,
                data: data,
                type: 'GET'
            };

            return this.request(settings);
        },
        post: function (url, data){
            var settings = {
                url: url,
                data: data,
                type: 'POST'
            };

            return this.request(settings);
        },
        onError: function () {

        },

        onWarning: function () {

        }
    }

    return http;
})()