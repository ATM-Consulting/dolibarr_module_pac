Number.prototype.formatMoney = function(c, d, t){
var n = this, 
    c = isNaN(c = Math.abs(c)) ? 2 : c, 
    d = d == undefined ? "." : d, 
    t = t == undefined ? "," : t, 
    s = n < 0 ? "-" : "", 
    i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", 
    j = (j = i.length) > 3 ? j % 3 : 0;
   return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
};
 
$(document).ready(function() {
	
	
	  
    $( "div.step>ul.connectedSortable" ).sortable({
      connectWith: ".connectedSortable"
      ,placeholder: 'receiver'
      ,receive:function(event, ui) {
      	
      	$li = ui.item;
      	var propalid = $li.attr('propal-id');
      	$ul = $li.closest('ul');
      	
      	var proba = $ul.attr('min');
      	
      	var end = $ul.attr('month_end');
      	
      	$.ajax({
			url:"script/interface.php"
			,data:{
				'put':'propal'
				,'propalid':propalid
				,'proba':proba
				,'end': end
			}
			,dataType:'json'
		});
      	
      }
    }).disableSelection();
  
	refreshPac();
	
	$('button#refresh').click(function() {
		refreshPac();
	});
});

function refreshPac() {
	
	
	$('div.step').find('ul').each(function(i, item) {
		
		var $ul = $(item);
		
		$ul.empty();
		
		var min = $ul.attr('min');
		var max = $ul.attr('max');
		var start = $ul.attr('month_start');
		var end = $ul.attr('month_end');
		var special = $ul.attr('special');
		
		$.ajax({
			url:"script/interface.php"
			,data:{
				'get':'propals'
				,'min':min
				,'max':max
				,'start':start
				,'end':end
				,'special':special
				,'fk_user':$('#fk_user').val()
				
			}
			,dataType:'json'
		}).done(function(data) {
			
			var total = 0;
			
			var height = 200;
			
			$.each(data, function(i,item) {
				
				$li = $('<li propal-id="'+item.id+'" />');
				$li.append('<h3>'+item.ref+' : '+item.total_ht_aff+'</h3>'); //TODO complete aff
				$li.append('<div>'+item.customerLink+'</div>');
				
				total+=parseFloat(item.total_ht);
				
				$ul.append($li);
				
				if(height<$li.height()) {
					height = $li.height();
					console.log(height);
				} 
				
			});
			
			$ul.closest('div').find('ul').css('min-height', height);
			
			$ul.closest('div').find('.total').html(total.formatMoney(2, ',', ' '));
			
		});
		
		
	});
	
}
