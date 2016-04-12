<?php

	require 'config.php';
	
	$action = GETPOST('action');
	
	//TODO conf
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
	
	$TPeriod=array(
		array(
			'label'=>'dans le mois'
			,'end'=>1 // nombre de jour mois
		)
		,array(
			'label'=>'entre 1 et 3 mois'
			,'start'=>1 
			,'end'=>3
		)
		,array(
			'label'=>'entre 3 et 9 mois'
			,'start'=>3 
			,'end'=>9
		
		)
		,array(
			'label'=>'au delà'
			,'start'=>9 
		)
	);
	
	switch ($action) {
		case 'value':
			
			break;
		default:
		
			_card($TPac,$TPeriod);
			
			break;
	}
	
function _card(&$TPac,&$TPeriod) {
	global $conf,$db,$user,$langs;
	
	llxHeader('', 'PAC', '', '', 0, 0, array('/pac/js/pac.js' ), array('/pac/css/pac.css') );
	dol_fiche_head();
	
	$form=new Form($db);

	echo '<div>';
	echo $form->select_dolusers($user->id,'fk_user',1);
	
	echo '<button id="refresh">'.$langs->trans('Refresh').'</button>';
	
	echo '</div><div style="clear: both;"></div>';
	
	$width = floor(100 / count($TPac) * 10) /10 ;
	
	foreach($TPac as $k=>&$TData) {
		?><div style="width:<?php echo $width; ?>%; display:inline-block;"><h2><?php echo $TData['label']; ?></h2></div><?php
	
	}
	foreach($TPeriod as $kp=>$period) {
		
		?>
		<div class="period"><?php echo $period['label']; ?></div>
		<div>
			<?php
		
		
		
		foreach($TPac as $k=>&$TData) {
			
			?>
			<div class="step" style="width:<?php echo $width; ?>%">
				<ul class="<?php echo empty($TData['special']) ? 'connectedSortable' : 'special'; ?>" id="step-<?php echo $kp.'-'.$k ?>" min="<?php echo __val($TData['min'],0) ?>" max="<?php echo __val($TData['max'],0) ?>"  month_start="<?php echo __val($period['start'],0) ?>"  month_end="<?php echo __val($period['end'],0) ?>"  special="<?php echo __val($TData['special'],'') ?>">
					
				</ul>
				<div class="total"></div>			
			</div>
			<?php	
		}
		
		?></div><div style="clear: both;"></div><?php
		
	}
	?>
	<div style="clear: both;"></div>
	<?php
	
	dol_fiche_end();
	llxFooter();
	
	
}
