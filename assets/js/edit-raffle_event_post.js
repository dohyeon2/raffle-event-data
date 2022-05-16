(($) => {
    const make_announce_btn_indicator = 'button.make-announce';
    const edit_announce_btn_indicator = 'button.edit-announce';
    //이벤트의 결과를 만드는 버튼 이벤트
    $(document).on("click", make_announce_btn_indicator, (e) => {
        const self = e.currentTarget;
        const { pid } = self.dataset;
        const { ajaxurl } = ajax_object;
        const data = {
            'action': 'raffle_event_make_announce',
            'pid': pid,
        };
        $.ajax({
            method: "POST",
            url: ajaxurl,
            data,
            success: (res) => {
                const { data } = res;
                const confirm = window.confirm(data.mssg);
                if (confirm) {
                    window.location.href = data.data.edit_url;
                }
            },
            error: (jqXHR) => {
                const { data } = jqXHR.responseJSON;
                const confirm = window.confirm(data.mssg);
                if (confirm) {
                    window.location.href = data.data.edit_url;
                }
            }
        });
    });
    $(document).on("click", edit_announce_btn_indicator, (e) => {
        const self = e.currentTarget;
        const { url } = self.dataset;
        window.location.href = url;
    });
})(jQuery);