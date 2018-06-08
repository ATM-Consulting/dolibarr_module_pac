<?php
	require('config.php');
	dol_include_once('/projet/class/project.class.php');
	dol_include_once('/projet/class/task.class.php');
	dol_include_once('/user/class/user.class.php');
	dol_include_once('/core/lib/usergroups.lib.php');
	dol_include_once('/comm/propal/class/propal.class.php');
	dol_include_once('/core/class/html.form.class.php');
	dol_include_once('/categories/class/categorie.class.php');
	

	
	/*
	 * VIEW
	 */
	
	llxHeader('',$langs->trans('CommercialFollowUp'),'', '', 0, 0, array(), array('/pac/css/pac.css') );
	
	print dol_get_fiche_head($langs->trans('CommercialFollowUp'));
	print_fiche_titre($langs->trans("CommercialFollowUp"));
	
	
	_print_filtres();
	_print_rapport();
	
	
	
	
	
	
	
	
	
	
	function _get_propales($iduser, $date_deb, $date_fin){
	    global $db,$sortorder,$sortfield;
		$sortfield = GETPOST('sortfield');
		$sortorder = GETPOST('sortorder');
		$TData = array();
		
		$type= GETPOST('type');
		if(empty($type))$type='signed';
		 
		if($type === 'signed' ){
			$date_field ='date_cloture';
			$statut = 2;
		}
		else{
			$date_field = 'date_valid';
			$statut = 1;
		} 
		
		// TODO: utiliser la date de signature
		
		$sql = 'SELECT SUM(p.total_ht) total_ht, count(p.rowid) totalcount, p.fk_statut, cs.fk_categorie, UNIX_TIMESTAMP(p.datep) time, MONTH(p.datep) month, YEAR(p.datep) year
			FROM '.MAIN_DB_PREFIX.'propal p 
            LEFT JOIN '.MAIN_DB_PREFIX.'societe_commerciaux sc ON (sc.fk_soc = p.fk_soc)
            LEFT OUTER JOIN '.MAIN_DB_PREFIX.'categorie_societe cs ON (cs.fk_soc = p.fk_soc)
            
			WHERE 1 ';
			
		if($iduser>0) $sql.= ' AND sc.fk_user = '.$iduser; 
		$sql.= ' AND (p.'.$date_field.' BETWEEN "'.$date_deb.'" AND "'.$date_fin.'") 
			AND p.fk_statut='.$statut.' ';
			
		$sql.= ' GROUP BY  cs.fk_categorie, p.fk_statut, MONTH(p.datep), YEAR(p.datep) ';
		
		if (!empty($sortfield) && !empty($sortorder)){
			$sql .= 'ORDER BY p.datep ASC';
		}

		$resql = $db->query($sql);
		//print $sql;
		if ($resql){
			while ($line = $db->fetch_object($resql)){
				// dispatch results
			    
			    if(empty($line->fk_categorie)){ $line->fk_categorie = 0; } // in NULL case
			    
			    $TData['data'][$line->fk_categorie][$line->year.'-'.$line->month][$line->fk_statut] = $line ; // les résultats brute
			    
			    $TData['dates'][$line->year.'-'.$line->month] = new stdClass(); $line->time ; // liste des mois
			    $TData['dates'][$line->year.'-'.$line->month]->time=$line->time;
			}
		}
		return $TData;
	}
	
	function _print_filtres(){
		global $db, $langs;
		
		$id=(int) GETPOST('id');
		
		$Tform = new TFormCore($_SERVER["PHP_SELF"],'formFiltres', 'POST');
		_get_filtre($Tform);
	}
	
	function _get_filtre($form){
		global $db, $langs;

		$formol = new Form($db);
	    
	    print '<div class="tabBar">';
	    print '<table>';
		print '<tr>';
		print '<td>Commercial : </td>';
		print '<td>';
		print $formol->select_users(GETPOST('userid'), 'userid', 1);
		print '</td>';
		print '<td>';
		print $formol->selectarray('type', array('signed'=>$langs->trans('Signed'),'valid'=>$langs->trans('Validated')), GETPOST('type') );
		print '</td>';
		print '</tr>';
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
	
	function _print_rapport(){
	    global $db, $langs,$sortorder,$sortfield;
		
		$idUser=GETPOST('userid');
		
		$date_d=str_replace('/', '-', GETPOST('date_deb'));
		$date_f=str_replace('/', '-', GETPOST('date_fin'));
		$date_deb=date('Y-m-d', strtotime($date_d));
		$date_fin=date('Y-m-d', strtotime($date_f));
			
		if(GETPOST('date_deb')=='')$date_deb=date('Y-m-d' , strtotime(date('Y-m-d'))-(60*60*24*30));
		if(GETPOST('date_fin')=='')$date_fin=date('Y-m-d');
		$param = '&userid='.$idUser.'&date_deb='.$date_deb.'&date_fin='.$date_fins;
		
			$TData = _get_propales($idUser, $date_deb, $date_fin);
		
			
			print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">';
		
			

			

			// TABLE HEAD
			print '<thead>';
			print '<tr class="liste_titre">';
			print '<th ></th>';
			foreach ($TData['dates'] as $dateInfos){
			    print '<th colspan="3"  class="border-left-heavy"  style="text-align:center;" >'.dol_print_date($dateInfos->time, '%B').'</th>';
			}
			print '<th colspan="3"  class="border-left-heavy" style="text-align:center;" >'.$langs->trans('Total').'</th>';
			print "</tr>";
			
			
			print '<tr class="liste_titre">';
			print '<th class="cellMinWidth" >'.$langs->trans('Sector').'</th>';
			foreach ($TData['dates'] as $dateInfos ){
			    print '<th class="border-left-heavy cellMinWidth"  style="text-align:center;" >'.$langs->trans('Realized').'</th>';
			    print '<th class="border-left-light cellMinWidth"  style="text-align:center;" >'.$langs->trans('Signed').'</th>';
			    print '<th class="border-left-light cellMinWidth"  style="text-align:center;" >'.$langs->trans('TransformationRatio').'</th>';
			}
			
			print '<th class="border-left-heavy cellMinWidth"  style="text-align:center;" >'.$langs->trans('Realized').'</th>';
			print '<th class="border-left-light cellMinWidth"  style="text-align:center;" >'.$langs->trans('Signed').'</th>';
			print '<th class="border-left-light cellMinWidth"  style="text-align:center;" >'.$langs->trans('TransformationRatio').'</th>';
			print "</tr>";
			print '</thead>';

			
			print '<tbody>';
			
			//$TData['data'][$line->fk_categorie][$line->year.'-'.$line->month][$line->fk_statut] = $line ; // les résultats brute
			foreach ($TData['data'] as $fk_categorie => $data ){
			    
			    $catLabel = $langs->trans('withoutCategory');
			    if(!empty($fk_categorie)){
			         $categorie = new Categorie($db);
			         $categorie->fetch($fk_categorie);
			         $catLabel = $categorie->label;
			    }
			    print '<tr class="oddeven">';
			    print '<th style="text-align:left;" >'.$catLabel.'</th>';
			    
			    foreach ($TData['dates'] as $dateKey =>& $dateInfos ){
			        
			        $dateInfos->totalRealised = 0;
			        $dateInfos->nbRealised = 0;
			        
			        $dateInfos->totalSigned = 0;
			        $dateInfos->nbSigned =0;
			        
			        $dateInfos->transormationRatio = 0;
			        
			        if(!empty($data[$dateKey]))
			        {
			            foreach ($data[$dateKey] as $statut => $object)
			            {
			                
			                if($object->fk_statut == Propal::STATUS_DRAFT){
			                    // nothing
			                }
			                elseif($object->fk_statut == Propal::STATUS_VALIDATED){
			                    $dateInfos->totalRealised += $object->total_ht;
			                    $dateInfos->nbRealised += $object->totalcount;
			                }
			                elseif($object->fk_statut == Propal::STATUS_SIGNED){
			                    $dateInfos->totalRealised += $object->total_ht;
			                    $dateInfos->nbRealised += $object->totalcount;
			                    
			                    $dateInfos->totalSigned += $object->total_ht;
			                    $dateInfos->nbSigned += $object->totalcount;
			                }
			                elseif($object->fk_statut == Propal::STATUS_NOTSIGNED){
			                    $dateInfos->totalRealised += $object->total_ht;
			                    $dateInfos->nbRealised += $object->totalcount;
			                }
			                elseif($object->fk_statut == Propal::STATUS_BILLED){
			                    $dateInfos->totalRealised += $object->total_ht;
			                    $dateInfos->nbRealised += $object->totalcount;
			                    
			                    $dateInfos->totalSigned += $object->total_ht;
			                    $dateInfos->nbSigned += $object->totalcount;
			                }
			            }
			        }
			        
			        if(!empty($dateInfos->nbSigned))
			        {
			            $dateInfos->transormationRatio = $dateInfos->nbSigned / $dateInfos->nbRealised * 100;
			            $dateInfos->transormationRatio = round($dateInfos->transormationRatio,2);
			        }
			       
			        print '<td class="border-left-heavy"  style="text-align:right;" >'.price($dateInfos->totalRealised).'</td>';
			        print '<td class="border-left-light"  style="text-align:right;" >'.price($dateInfos->totalSigned).'</td>';
			        print '<td class="border-left-light"  style="text-align:right;" >'.price($dateInfos->transormationRatio).'%</td>';
			        
			    }
			    
			    print '<td class="border-left-heavy"  style="text-align:right;" ></td>';
			    print '<td class="border-left-light"  style="text-align:right;" ></td>';
			    print '<td class="border-left-light"  style="text-align:right;" ></td>';
			    
			    print '</tr>';
			    
			    
			}
			
			
			print '</tbody>';
			
		
			print '</table>';
		
		
	}
