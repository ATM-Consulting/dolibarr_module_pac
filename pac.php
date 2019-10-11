<?php

	require 'config.php';
	
	$action = GETPOST('action');

	$TInterests = array(
	    array(
	        'code' => ''
            , 'label' => 'Non qualifié'
            , 'fromDict' => false
            , 'sortable' => true
        )
    );

	$sql = 'SELECT code, label
            FROM ' . MAIN_DB_PREFIX . 'c_pac_interest
            WHERE active = 1';

	$resql = $db->query($sql);

	if(! $resql)
    {
        llxHeader('', 'PAC');

        dol_print_error($db);

        llxFooter();
        $db->close();

        exit;
    }

	$num = $db->num_rows($resql);

	for($i = 0; $i < $num; $i++)
    {
        $interest = $db->fetch_array($resql);
        $interest['fromDict'] = true;
	    $TInterests[] = $interest;
    }

	$TInterests[] = array(
		'code' => '__STATUS_SIGNED'
        , 'label' => 'Signé'
        , 'fromDict' => false
    );

	$TInterests[] =	array(
		'code' => '__STATUS_NOTSIGNED'
        , 'label' => 'Non signé'
        , 'fromDict' => false
    );


	$TPeriod=array(
		array(
			'label'=>'Dans le mois'
			,'end'=>1 // nombre de jour mois
		)
		,array(
			'label'=>'Entre 1 et 3 mois'
			,'start'=>1 
			,'end'=>3
		)
		,array(
			'label'=>'Entre 3 et 9 mois'
			,'start'=>3 
			,'end'=>9
		
		)
		,array(
			'label'=>'Au-delà'
			,'start'=>9 
		)
	);
	
	switch ($action) {
		case 'value':
			
			break;
		default:
		
			_card($TInterests,$TPeriod);
			
			break;
	}
	
function _card(&$TInterests, &$TPeriod) {
	global $conf,$db,$user,$langs;
	
	llxHeader('', 'PAC', '', '', 0, 0, array('/pac/js/pac.js' ), array('/pac/css/pac.css') );
	dol_fiche_head();
	
	$form=new Form($db);

	echo '<div>' . $langs->trans('Author') . '&nbsp;: ';
	echo $form->select_dolusers($user->id,'fk_user',1);
	
	echo '&nbsp;<button id="refresh">'.$langs->trans('Refresh').'</button>';
	
	echo '</div>';
	
	$width = floor(100 / count($TInterests) * 10) /10 ;
	
	foreach($TInterests as $k=> &$TData)
	{
		?><div style="width:<?php echo $width; ?>%; display:inline-block;"><h2><?php echo $TData['label']; ?></h2></div><?php
	
	}

	foreach($TPeriod as $kp=>$period) {
		
		?>
		<div class="period"><?php echo $period['label']; ?></div>
		<div>
			<?php
		
		
		
		foreach($TInterests as $k=>&$TData) {

			?>
			<div class="step" style="width:<?php echo $width; ?>%">
				<ul class="<?php echo ! empty($TData['fromDict']) ? 'connectedSortable' : (!empty($TData['sortable']) ? 'connectedSortable special' : 'special'); ?>"
                    id="step-<?php echo $kp.'-'.$k ?>"
                    data-code="<?php echo dol_escape_htmltag($TData['code']); ?>"
                    data-month-start="<?php echo __val($period['start'],0) ?>"
                    data-month-end="<?php echo __val($period['end'],0) ?>">
					
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
