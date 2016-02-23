<?php

	require 'config.php';
	
	$action = GETPOST('action');
	
	$TPac=array(
		/*array(
			'Label'=>'Opportunity'
			,'special'=>'budget'
		)
		,*/array(
			'label'=>'Premier contact' //TODO trans
			,'min'=>0
			,'max'=>10
		)
		,array(
			'label'=>'Attente de demo'
			,'min'=>10
			,'max'=>30
		)
		,array(
			'label'=>'intéressé'
			,'min'=>30
			,'max'=>60
		)
		,array(
			'label'=>'très intéressé'
			,'min'=>60
			,'max'=>90
		)
		
		//TODO special signé, non signé
		//TODO finir le module, il ne s'agissait que d'une esquisse
	);
	
	switch ($action) {
		case 'value':
			
			break;
		default:
		
			_card($TPac);
			
			break;
	}
	
function _card(&$TPac) {
	global $conf,$db,$user,$langs;
	
	llxHeader('', $title='PAC', '', '', 0, 0, array('/pac/js/pac.js' ), array('/pac/css/pac.css') );
	dol_fiche_head();
	
	$width = 100 / count($TPac) - 1;
	
	foreach($TPac as $k=>&$TData) {
		
		?>
		<div class="step" style="width:<?php echo $width; ?>%">
			<?php echo $TData['label']; ?>
			<ul class="connectedSortable" id="step-<?php echo $k ?>" min="<?php echo __val($TData['min'],0) ?>" max="<?php echo __val($TData['max'],0) ?>"  special="<?php echo __val($TData['special'],'') ?>">
				
			</ul>			
		</div>
		<?php	
	}
	
	?>
	<div style="clear: both;"></div>
	<?php
	
	dol_fiche_end();
	llxFooter();
	
	
}
