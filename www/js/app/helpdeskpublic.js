/* 
 * KlientiCZ
 * Tomáš Halász - 2H C.S. s.r.o.
 * 07.03.2017 - 10:20:55
 * 
 */


$(document).on('change','#frm-eventPublicForm-cl_partners_book_id', function(e) {
	var urlString  = $('#frm-eventPublicForm-cl_partners_book_id').data('urlajax');
	var partners_book = this;
	console.log(urlString);
	//data = $(this).val();

	var myParams = new Object();		
	myParams.cl_partners_book_id = $(this).val();	
	var slaveWorkers = $(partners_book).data('slave_workers');
	var slave = partners_book.form[slaveWorkers];	
	var slaveCategories = $(partners_book).data('slave_categories');
	var slave2 = partners_book.form[slaveCategories];		
	
	$.getJSON(urlString, myParams, function (data) {
			updateSelect(slave, data['worker']);
			updateSelect(slave2, data['categories']);
			//updateWorkerInfo(data['workerinfo']);
			//set default 
		}); 		
	
    }
);

function updateSelect(select, data)
{
	$(select).empty();
	//console.log(data);
	for (var id in data['arrData']) {
		if (id == data['def'])
		{
			$('<option>').attr('value', id).attr('selected','selected').text(data['arrData'][id]).appendTo(select);
		}else{
			$('<option>').attr('value', id).text(data['arrData'][id]).appendTo(select);
		}
	}
	//alert(data['def']);
}	
