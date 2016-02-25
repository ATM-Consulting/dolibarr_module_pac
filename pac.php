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
		,array(
			'label'=>'Signé'
			,'min'=>100
			,'max'=>100
			,'special'=>'signed'
		)
		,array(
			'label'=>'Non signé'
			,'min'=>100
			,'max'=>100
			,'special'=>'notsigned'
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
	global $conf,$db,$user,$langs,$form;
	
	llxHeader('', 'PAC', '', '', 0, 0, array('/pac/js/pac.js' ), array('/pac/css/pac.css') );
	dol_fiche_head();
	
	echo '<div>';
	$form->select_users($user->id,'fk_user',1,'',0,'','');
	
	echo '<button id="refresh">'.$langs->trans('Refresh').'</button>';
	
	echo '</div><div style="clear: both;"></div>';
	
	$width = 100 / count($TPac) - 1;
	
	foreach($TPac as $k=>&$TData) {
		
		?>
		<div class="step" style="width:<?php echo $width; ?>%">
			<h2><?php echo $TData['label']; ?></h2>
			<ul class="<?php echo empty($TData['special']) ? 'connectedSortable' : 'special'; ?>" id="step-<?php echo $k ?>" min="<?php echo __val($TData['min'],0) ?>" max="<?php echo __val($TData['max'],0) ?>"  special="<?php echo __val($TData['special'],'') ?>">
				
			</ul>
			<div class="total"></div>			
		</div>
		<?php	
	}
	
	?>
	<div style="clear: both;"></div>
	<?php
	
	dol_fiche_end();
	llxFooter();
	
	
}
