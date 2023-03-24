//05.08.2019 - save form after pressing ENTER on quantity
$(document).on('keypress', '#frm-searchItem-searchCode', function (e) {
    var charCode = e.charCode || e.keyCode;
    if (charCode  == 13) { //Enter key's keycode
        //$(this).closest('form').send;
        $(this).closest('form').find('[name="send"]').click();
    }
});
