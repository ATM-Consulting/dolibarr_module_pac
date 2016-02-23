$(document).ready(function() {
	
	
	  
    $( "div.step>ul" ).sortable({
      connectWith: ".connectedSortable"
      ,receive:function(event, ui) {
      	
      	$li = ui.item;
      	var propalid = $li.attr('propal-id');
      	$ul = $li.closest('ul');
      	var proba = $ul.attr('min');
      	
      	$.ajax({
			url:"script/interface.php"
			,data:{
				'put':'propal'
				,'propalid':propalid
				,'proba':proba
			}
			,dataType:'json'
		});
      	
      }
    }).disableSelection();
  
	$('div.step').find('ul').each(function(i, item) {
		
		var $ul = $(item);
		
		var min = $ul.attr('min');
		var max = $ul.attr('max');
		
		$.ajax({
			url:"script/interface.php"
			,data:{
				'get':'propals'
				,'min':min
				,'max':max
			}
			,dataType:'json'
		}).done(function(data) {
			
			$.each(data, function(i,item) {
				
				$li = $('<li propal-id="'+item.id+'" />');
				$li.append('<h3>'+item.ref+'</h3>'); //TODO complete aff
				
				$ul.append($li);
				
			});
			
			
		});
		
		
	});
	
});
