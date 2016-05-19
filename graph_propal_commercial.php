<?php
	require('config.php');
	dol_include_once('/projet/class/project.class.php');
	dol_include_once('/projet/class/task.class.php');
	dol_include_once('/user/class/user.class.php');
	dol_include_once('/core/lib/usergroups.lib.php');
	dol_include_once('/comm/propal/class/propal.class.php');
	
	
	
	llxHeader('',$langs->trans('graphPropalCommercial'));
	
	print dol_get_fiche_head($langs->trans('graphPropalCommercial'));
	print_fiche_titre($langs->trans("graphPropalCommercial"));
	
	
	$date_deb= date('Y-m-d');
	_print_filtres();
	_print_graph();
	
	
	function _get_propales_commercial($date_deb, $date_fin){
		global $db;
		
		$TData = array();
		
		
		$sql = 'SELECT COUNT(p.rowid) AS nbPropales, p.tms AS dateO, p.fk_user_author AS auteur, SUM(p.total_ht) AS total ';
		$sql .= 'FROM '.MAIN_DB_PREFIX.'propal p ';
		$sql .= 'WHERE p.tms BETWEEN "'.$date_deb.'" AND "'.$date_fin.'" ';
		$sql .= 'GROUP BY p.fk_user_author ';
		$sql .= 'ORDER BY p.rowid ';

		$resql = $db->query($sql);
		
		if ($resql){
			while ($line = $db->fetch_object($resql)){
				
				$TData[] = array(
					"nbPropales" => $line->nbPropales,
					"total" => $line->total,
					"date"	=> $line->dateO,
					"auteur"=> $line->auteur
				);
			}
		}
		return $TData;
		
	}
	
	function _print_graph(){
		global $db, $langs;
		
		
		$date_d=preg_replace('/\//','-',GETPOST('date_deb'));
		$date_f=preg_replace('/\//','-',GETPOST('date_fin'));
		$date_deb=date('Y-m-d', strtotime($date_d));
		$date_fin=date('Y-m-d', strtotime($date_f));
		
		$PDOdb = new TPDOdb;
		
		if(empty(GETPOST('date_deb')))$date_deb=date('Y-m-d' , strtotime(date('Y-m-d'))-(60*60*24*30));
		if(empty(GETPOST('date_fin')))$date_fin=date('Y-m-d');
		
		$TDataBrut=_get_propales_commercial($date_deb, $date_fin);
		$TData = array();
		//var_dump($TDataBrut);
		
		foreach ($TDataBrut as $line){
			$user = new User($db);
			$user->fetch($line['auteur']);
			$TData[]=array(
				"Commercial" 		=> $user->firstname.' '.$user->lastname,
				//"Chiffre"    		=> $line['total'],
				"Nombre de propales"=> $line['nbPropales']
 			);
		}
		
		$explorer = new stdClass();
		$explorer->actions = array("dragToZoom", "rightClickToReset");
		
		
		$listeview = new TListviewTBS('graphPropalCommercial');
		
		print $listeview->renderArray($PDOdb, $TData
			,array(
				'type' => 'chart'
				,'chartType' => 'ColumnChart'
				,'liste'=>array(
					'titre'=> $langs->transnoentities('graphPropalCommercial')
				)
				,'hAxis'=>array('title'=> 'Commercial')
				,'vAxis'=>array('title'=> 'Chiffre')
				,'explorer'=>$explorer
			)
		);
	}


	function _print_filtres(){
		global $db, $langs;
		
		$id=(int) GETPOST('id');
		
		$Tform = new TFormCore($_SERVER["PHP_SELF"],'formFiltres', 'POST');
		_get_filtre($Tform);
	}
	
	
	function _get_filtre($form){
	    
	    print '<div class="tabBar">';
	    print '<table>';
		print '<tr>';
		print '<td>Date de début : </td>';
		print '<td>'.$form->calendrier('', 'date_deb', ($_REQUEST['date_deb'])? $_REQUEST['date_deb'] : '').'</td>';
		print '</tr>';
		print '<tr>';
		print '<td>Date de fin : </td>';
		print '<td>'.$form->calendrier('', 'date_fin', ($_REQUEST['date_fin'])? $_REQUEST['date_fin'] : '').'</td>';
		print '</tr>';
	
	    print '<tr><td colspan="2" align="center">'.$form->btsubmit('Valider', '').'</td></tr>';
	    print '</table>';
	    
	    print '</div>';
	}
