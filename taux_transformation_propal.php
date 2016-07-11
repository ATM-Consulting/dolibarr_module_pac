<?php
	require('config.php');
	dol_include_once('/projet/class/project.class.php');
	dol_include_once('/projet/class/task.class.php');
	dol_include_once('/user/class/user.class.php');
	dol_include_once('/core/lib/usergroups.lib.php');
	dol_include_once('/comm/propal/class/propal.class.php');
	
	
	
	llxHeader('',$langs->trans('PropalTransformed'));
	
	print dol_get_fiche_head($langs->trans('PropalTransformed'));
	print_fiche_titre($langs->trans("PropalTransformed"));
	
	
	_print_filtres();
	_print_graph();
	
	
	function _get_propales_commercial($date_deb, $date_fin){
		global $db;
		
		$TData = array();
		
		
		$sql = 'SELECT COUNT(p.rowid) AS nbPropales, p.fk_user_author AS auteur ';
		$sql .= 'FROM '.MAIN_DB_PREFIX.'propal p ';
		$sql .= 'WHERE (p.fk_statut = 1) AND  (p.datec BETWEEN "'.$date_deb.'" AND "'.$date_fin.'") ';
		$sql .= 'GROUP BY p.fk_user_author ';
		$sql .= 'ORDER BY p.fk_user_author ';

		$resql = $db->query($sql);
		
		if ($resql){
			while ($line = $db->fetch_object($resql)){
				
				$TData[$line->auteur] = array(
					"ouvertes" => $line->nbPropales,
					"auteur"=> $line->auteur
				);
			}
		}
		return $TData;
		
	}

function _get_propales_signees_commercial($date_deb, $date_fin){
		global $db;
		
		$TData = array();
		//TODO Date modif entre deb et fin, et statut à signé
		
		$sql = 'SELECT COUNT(p.rowid) AS nbPropales, p.fk_user_cloture AS auteur ';
		$sql .= 'FROM '.MAIN_DB_PREFIX.'propal p ';
		$sql .= 'WHERE p.fk_statut=2 AND (p.date_cloture BETWEEN "'.$date_deb.'" AND "'.$date_fin.'") ';
		$sql .= 'GROUP BY p.fk_user_cloture ';
		$sql .= 'ORDER BY p.fk_user_cloture ';
		
		$resql = $db->query($sql);
		
		if ($resql){
			while ($line = $db->fetch_object($resql)){
				
				$TData[$line->auteur] = array(
					"signees" => $line->nbPropales,
					"auteur"=> $line->auteur
				);
			}
		}
		return $TData;
		
	}
	
	function _print_graph(){
		global $db, $langs;
		
		
		$date_d=str_replace('/', '-', GETPOST('date_deb'));
		$date_f=str_replace('/', '-', GETPOST('date_fin'));
		$date_deb=date('Y-m-d', strtotime($date_d));
		$date_fin=date('Y-m-d', strtotime($date_f));
		
		$TData =array();
		$PDOdb = new TPDOdb;
		
		if(GETPOST('date_deb')=='')$date_deb=date('Y-m-d' , strtotime(date('Y-m-d'))-(60*60*24*30));
		if(GETPOST('date_fin')=='')$date_fin=date('Y-m-d');
		
		$TPropales= _get_propales_commercial($date_deb, $date_fin);
		$TPropalesSignees = _get_propales_signees_commercial($date_deb, $date_fin);
		//$TData = array_merge_recursive($TPropales,$TPropalesSignees);

		foreach ($TPropales as $ouverte){
			//var_dump($ouverte);


				//TODO finir le reformatage du tableau
				if(!isset($TData[$ouverte['auteur']])){
					$user = new User($db);
					$user->fetch($ouverte['auteur']);
					$TData[$ouverte['auteur']] = array(
								'auteur'   => $user->lastname." ".$user->firstname,
								'signees'  => $TPropalesSignees[$ouverte['auteur']]['signees'],
								'ouvertes' => $TPropales[$ouverte['auteur']]['ouvertes']
							);
				}else if(isset($TData[$ouverte['auteur']])){
					$user = new User($db);
					$user->fetch($ouverte['auteur']);
					$TData[$ouverte['auteur']]['signees'] += $TPropalesSignees[$ouverte['auteur']]['signees'];
					$TData[$ouverte['auteur']]['signees'] += $TPropales[$ouverte['auteur']]['ouvertes'];
				}
			
		}


		//var_dump($TDataBrut);
		

		
		$explorer = new stdClass();
		$explorer->actions = array("dragToZoom", "rightClickToReset");
		
		
		$listeview = new TListviewTBS('PropalTransformed');
		
		print $listeview->renderArray($PDOdb, $TData
			,array(
				'type' => 'chart'
				,'chartType' => 'ColumnChart'
				,'liste'=>array(
					'titre'=> $langs->transnoentities('PropalTransformed')
				)
				,'hAxis'=>array('title'=> 'commercial')
				,'vAxis'=>array('title'=> 'Nombre de propales')
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
		print '<td>'.$form->calendrier('', 'date_deb', (GETPOST('date_deb'))? GETPOST('date_deb') : '').'</td>';
		print '</tr>';
		print '<tr>';
		print '<td>Date de fin : </td>';
		print '<td>'.$form->calendrier('', 'date_fin', (GETPOST('date_fin'))? GETPOST('date_fin') : '').'</td>';
		print '</tr>';
	
	    print '<tr><td colspan="2" align="center">'.$form->btsubmit('Valider', '').'</td></tr>';
	    print '</table>';
	    
	    print '</div>';
	}
