(($) => {
    //상수 선언
    const DUETYPE_NAME = 'due_type';

    //선택된 종료 타입
    const inputElChecked = $('input[name="' + DUETYPE_NAME + '"][checked]');
    const getInputElCheckedValue = () => inputElChecked.val();

    /**
     * @param {object} callback 도큐멘트 렌더링 시 실행될 함수
     * @returns {void}
     */
    function init(callback) {
        $(document).ready(() => {
            callback();
        });
    }

    /**
     * 종료 타입에 따른 테이블로우 스왑
     * @param {string} value 종료 타입 값
     * @returns {void}
     */
    function swapTr(value) {
        enableInput(`set-${value}`);
        $(`input[name="due_type"]:not([value="${value}"])`).each((i, v) => {
            disableInput(`set-${v.value}`);
        });
    }

    /**
     * 특정 클래스의 input을 필수화하고, 부모의 display를 회복시킴
     * @param {string} className 
     */
    function enableInput(className) {
        $(`tr.${className} input`).attr("required", true);
        $(`tr.${className}`).css({
            display: "table-row"
        });
    };

    /**
     * 특정 클래스의 input을 필수화를 해제하고, 부모의 display를 삭제시킴
     * @param {string} className 
     */
    function disableInput(className) {
        $(`tr.${className} input`).attr("required", false);
        $(`tr.${className}`).css({
            display: "none"
        });
    };

    init(() => {
        swapTr(getInputElCheckedValue());
    });


    $(document).on("change", 'input[name="' + DUETYPE_NAME + '"]', (e) => {
        const { target } = e;
        swapTr(target.value);
    });

})(jQuery);

(($) => {
    //중복금지 컨트롤
    const button = $('#duplication-button');
    const getInput = () => $('input[name="duplication"]');
    function ChangeBtn() {
        const value = getInput().val();
        if (value !== "0") {
            button.html("중복허용");
        } else {
            button.html("중복금지");
        }
    }
    function toggleValue() {
        const value = getInput().val();
        const input = getInput();
        if (value === "0") {
            input.val("1");
        } else {
            input.val("0");
        }
        ChangeBtn();
    }
    button.on('click', (e) => {
        toggleValue();
    });
})(jQuery);

(($) => {
    function add_item_to_list(value) {
        const matches = value.match(/([^(]+)\s\((.+)\)/);
        const [all, label, id] = matches;
        if ($(`.added_nft_data_item#item-${id}`).length > 0) {
            window.alert("이미 등록된 nft 입니다");
            return;
        }
        const add_nft_data = $("<div class='added_nft_data_item' id='item-" + id + "'></div>");
        const add_nft_label = $("<div class='item_label'></div>");
        add_nft_label.css({
            padding: 5,
        });
        const add_nft_delete_button = $("<button class='item_delete_button button button-small button-secondary'>삭제</button>");
        const add_nft_data_hidden_input = $("<input type='hidden' name='nft_list[]'/>");
        add_nft_data_hidden_input.attr("value", label.trim().replaceAll(" ", "_") + "-" + id);
        add_nft_label.append($("<span style='margin-right:10px;'>" + label + "</span>"), add_nft_delete_button);
        add_nft_data.append(add_nft_label, add_nft_data_hidden_input);
        added_nft_list.append(add_nft_data);
    }
    const added_nft_list = $("#added_nft_list");
    $(document).on('click', '.added_nft_data_item button.item_delete_button', (e) => {
        $(e.target).closest('.added_nft_data_item').remove();
    });
    function addItemFunc() {
        const value = $('#nft_list_input').val();
        if (value !== "") {
            add_item_to_list(value);
            $('#nft_list_input').val("");
            $('#nft_list_input')[0].focus();
        }
    }
    $('#nft_list_input_button').on('click', (e) => {
        addItemFunc();
    });
    nft_lists.forEach(e => {
        const value = e.replaceAll("_", " ").replace(/\-(.+)/, ` ($1)`);
        add_item_to_list(value);
    });
})(jQuery);

(($) => {

    $('.media-button').on('click', (e) => {
        const { target } = e;
        const { label, type } = target.dataset;
        const frame = wp.media({
            title: label,
            button: {
                text: target.innerHTML
            },
            multiple: false  // Set to true to allow multiple files to be selected
        });
        frame.on('select', function () {

            // Get media attachment details from the frame state
            const attachment = frame.state().get('selection').first().toJSON();
            const { id, url } = attachment;

            const container = $(`.${type}-img`);
            container.parent().find('input').remove();
            container.css({
                backgroundImage: `url(${url})`
            });
            const hiddenInput = $(`<input type="hidden" name="${type}_img" value="${id}"/>`);
            container.after(hiddenInput);

        });

        // Finally, open the modal on click
        frame.open();
    });
})(jQuery);

(($) => {
    const INPUT_GROUP_BTN_INDICATOR = '#nft_group_input_button';
    const NFT_GROUP_FORM_INDICATOR = '#nft_group_form';
    const input_group_btn = $(INPUT_GROUP_BTN_INDICATOR);
    const nft_group_form = $(NFT_GROUP_FORM_INDICATOR);
    nft_group_form.on('submit', (e) => {
        e.preventDefault();
        const input = $(e.target).find("input");
        const value = input.val();
        console.log(value);
    });
})(jQuery);