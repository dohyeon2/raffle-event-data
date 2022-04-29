(($) => {
    toggleTr($('input[name="due_type"][checked]').val());
    $(document).on("change", 'input[name="due_type"]', (e) => {
        const { target } = e;
        toggleTr(target.value);
    });

    function toggleTr(value) {
        enableInput(`set-${value}`);
        $(`input[name="due_type"]:not([value="${value}"])`).each((i, v) => {
            disableInput(`set-${v.value}`);
        });
    }

    function enableInput(className) {
        $(`tr.${className} input`).attr("required", true);
        $(`tr.${className}`).css({
            display: "table-row"
        });
    };
    function disableInput(className) {
        $(`tr.${className} input`).attr("required", false);
        $(`tr.${className}`).css({
            display: "none"
        });
    };
})(jQuery);

(($)=>{
    function add_time_to_list(value){
        const matches = value.match(/([^(]+)\s\((\d+)\)/);
        const [all,label,id] = matches;
        if($(`.added_nft_data_item#item-${id}`).length > 0 ){
            window.alert("이미 등록된 nft 입니다");
            return;
        }
        const add_nft_data = $("<div class='added_nft_data_item' id='item-"+id+"'></div>");
        const add_nft_label = $("<div class='item_label''></div>");
        const add_nft_delete_button = $("<button class='item_delete_button button button-small button-secondary'>삭제</button>");
        const add_nft_data_hidden_input = $("<input type='hidden' name='nft_list[]'/>");
        add_nft_data_hidden_input.attr("value",label.trim().replace(" ","_")+"-"+id);
        add_nft_label.append($("<span>"+label+"</span>"),add_nft_delete_button);
        add_nft_data.append(add_nft_label,add_nft_data_hidden_input);
        added_nft_list.append(add_nft_data);
    }
    const added_nft_list = $("#added_nft_list");
    $(document).on('click','.added_nft_data_item button.item_delete_button',(e)=>{
        $(e.target).closest('.added_nft_data_item').remove();
    });
    $('#nft_list_input_button').on('click',(e)=>{
        const value = $('#nft_list_input').val();
        if(value !== ""){
            add_time_to_list(value);
        }
    });
    nft_lists.forEach(e => {
        const value = e.replace("_"," ").replace(/\-(\d+)/,` ($1)`);
        add_time_to_list(value);
    });
})(jQuery);