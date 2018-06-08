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
		
		$date_field ='COALESCE(NULLIF(p.date_cloture,\'\'), p.date_valid)'; //'date_valid'
		
		
		
		
		// TODO: utiliser la date de signature
		
		$sql = 'SELECT SUM(p.total_ht) total_ht, count(p.rowid) totalcount, p.fk_statut, cs.fk_categorie, UNIX_TIMESTAMP('.$date_field.') time, MONTH('.$date_field.') month, YEAR('.$date_field.') year
			FROM '.MAIN_DB_PREFIX.'propal p 
            LEFT JOIN '.MAIN_DB_PREFIX.'societe_commerciaux sc ON (sc.fk_soc = p.fk_soc)
            LEFT OUTER JOIN '.MAIN_DB_PREFIX.'categorie_societe cs ON (cs.fk_soc = p.fk_soc)
            
			WHERE 1 ';
			
		if($iduser>0) $sql.= ' AND sc.fk_user = '.$iduser; 
		$sql.= ' AND ('.$date_field.' BETWEEN "'.$date_deb.'" AND "'.$date_fin.'") 
			AND p.fk_statut='.$statut.' ';
			
		$sql.= ' GROUP BY  cs.fk_categorie, p.fk_statut, MONTH('.$date_field.'), YEAR('.$date_field.') ';
		
		if (!empty($sortfield) && !empty($sortorder)){
		    $sql .= 'ORDER BY '.$date_field.' ASC';
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
	    
		if(GETPOST('date_deb')=='')$default_date_deb=date('Y-m' , strtotime(date('Y-m-d'))-(60*60*24*30));
		if(GETPOST('date_fin')=='')$default_date_fin=date('Y-m');
		
		
	    print '<div class="tabBar">';
	    print '<table>';
		print '<tr>';
		print '<td>Commercial : </td>';
		print '<td>';
		print $formol->select_users(GETPOST('userid'), 'userid', 1);
		print '</td>';
		print '<td>';
		//print $formol->selectarray('type', array('signed'=>$langs->trans('Signed'),'valid'=>$langs->trans('Validated')), GETPOST('type') );
		print '</td>';
		print '</tr>';
		print '<tr>';
		print '<td>Date de début : </td>';
		print '<td><input type="month" min="month"   name="date_deb" value="'.((GETPOST('date_deb'))? GETPOST('date_deb') : $default_date_deb).'"  /></td>';// '.$form->calendrier('', 'date_deb', (GETPOST('date_deb'))? GETPOST('date_deb') : $default_date_deb).'
		print '</tr>';
		print '<tr>';
		print '<td>Date de fin : </td>';
		print '<td><input type="month" min="month"  name="date_fin" value="'.((GETPOST('date_fin'))? GETPOST('date_fin') : $default_date_fin).'"  /></td>';// '.$form->calendrier('', 'date_fin', (GETPOST('date_fin'))? GETPOST('date_fin') : $default_date_fin).'</td>';
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
		$date_deb=date('Y-m-01', strtotime($date_d));
		$date_fin=date('Y-m-t', strtotime($date_f));
			
		if(GETPOST('date_deb')=='')$date_deb=date('Y-m-01' , strtotime(date('Y-m-01'))-(60*60*24*30));
		if(GETPOST('date_fin')=='')$date_fin=date('Y-m-t');
		$param = '&userid='.$idUser.'&date_deb='.$date_deb.'&date_fin='.$date_fins;
		
			$TData = _get_propales($idUser, $date_deb, $date_fin);
		
			if(empty($TData)) return ;
			
			print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">';
		
			

			

			// TABLE HEAD
			print '<thead>';
			print '<tr class="liste_titre">';
			print '<th ></th>';
			foreach ($TData['dates'] as $dateInfos){
			    print '<th colspan="3"  class="border-left-heavy"  style="text-align:center;" >'.dol_print_date($dateInfos->time, '%B %Y').'</th>';
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
			
			// INIT DATE INFOS TOTALS
			prepareTData($TData);
			
			
			
			foreach ($TData['data'] as $fk_categorie => $data ){
			    
			    $catLabel = $langs->trans('withoutCategory');
			    if(!empty($fk_categorie)){
			         $categorie = new Categorie($db);
			         $categorie->fetch($fk_categorie);
			         $catLabel = $categorie->label;
			    }
			    print '<tr class="oddeven">';
			    print '<th style="text-align:left;" >'.$catLabel.'</th>';
			    
			    $sector_totalRealised = 0;
			    $sector_nbRealised = 0;
			    
			    $sector_totalSigned = 0;
			    $sector_nbSigned =0;
			    
			    
			    foreach ($TData['dates'] as $dateKey => $dateInfos ){
			        
			        $totalRealised = 0;
			        $nbRealised = 0;
			        
			        $totalSigned = 0;
			        $nbSigned =0;
			        
			        $transormationRatio = 0;
			        
			        
			        // AFFECTATION DES TOTAUX AUX DATES
			        if(!empty($data[$dateKey]))
			        {
			            foreach ($data[$dateKey] as $statut => $object)
			            {
			                
			                if($object->fk_statut == Propal::STATUS_DRAFT){
			                    // nothing
			                }
			                elseif($object->fk_statut == Propal::STATUS_VALIDATED){
			                    $totalRealised += $object->total_ht;
			                    $nbRealised += $object->totalcount;
			                }
			                elseif($object->fk_statut == Propal::STATUS_SIGNED){
			                    $totalRealised += $object->total_ht;
			                    $nbRealised += $object->totalcount;
			                    
			                    $totalSigned += $object->total_ht;
			                    $nbSigned += $object->totalcount;
			                }
			                elseif($object->fk_statut == Propal::STATUS_NOTSIGNED){
			                    $totalRealised += $object->total_ht;
			                    $nbRealised += $object->totalcount;
			                }
			                elseif($object->fk_statut == Propal::STATUS_BILLED){
			                    $totalRealised += $object->total_ht;
			                    $nbRealised += $object->totalcount;
			                    
			                    $totalSigned += $object->total_ht;
			                    $nbSigned += $object->totalcount;
			                }
			            }
			        }
			        
			        if(!empty($nbSigned))
			        {
			            $transormationRatio = $nbSigned / $nbRealised * 100;
			            $transormationRatio = round($transormationRatio,2);
			        }
			       
			        $transformationRatio = calcRatio($nbSigned, $nbRealised);
			        
			        print '<td class="border-left-heavy"  style="text-align:right;" >'.price($totalRealised).'</td>';
			        print '<td class="border-left-light"  style="text-align:right;" >'.price($totalSigned).'</td>';
			        print '<td class="border-left-light"  style="text-align:right;" >'.price($transformationRatio).'%</td>';
			        
			        
			        
			        // Mise à jour des totaux secteur (ligne)
			        $sector_totalRealised += $totalRealised;
			        $sector_nbRealised += $nbRealised;
			        
			        $sector_totalSigned += $totalSigned;
			        $sector_nbSigned += $nbSigned;
			        
			        // Mise à jour des totaux du bloc date
			        $TData['dates'][$dateKey]->totalRealised += $totalRealised;
			        $TData['dates'][$dateKey]->nbRealised += $nbRealised;
			        
			        $TData['dates'][$dateKey]->totalSigned += $totalSigned;
			        $TData['dates'][$dateKey]->nbSigned += $nbSigned;
			       
			    }
			    
			    
			    $sector_transformationRatio = calcRatio($sector_nbSigned, $sector_nbRealised);
			    
			    
			    print '<td class="border-left-heavy"  style="text-align:right;" >'.price($sector_totalRealised).'</td>';
			    print '<td class="border-left-light"  style="text-align:right;" >'.price($sector_totalSigned).'</td>';
			    print '<td class="border-left-light"  style="text-align:right;" >'.price($sector_transformationRatio).'%</td>';
			    
			    print '</tr>';
			    
			}
			print '</tbody>';
			
			
			
			print '<tfoot>';
			
			// TOTAL LINE
			print '<tr  class="oddeven" >';
			print '<th style="text-align:right;" >'.$langs->trans('Total').'</th>';
			
			
			$global_totalRealised=0;
			$global_totalSigned=0;
			$global_nbRealised=0;
			$global_nbSigned=0;
			
			foreach ($TData['dates'] as  $dateKey => $dateInfos  ){
			    
			    $dateInfos->transormationRatio = calcRatio($dateInfos->nbSigned, $dateInfos->nbRealised);
			    
			    print '<th class="border-left-heavy"  style="text-align:right;" >'.price($dateInfos->totalRealised).'</th>';
			    print '<th class="border-left-light"  style="text-align:right;" >'.price($dateInfos->totalSigned).'</th>';
			    print '<th class="border-left-light"  style="text-align:right;" >'.price($dateInfos->transormationRatio).'%</th>';
			    
			    $global_totalRealised += $dateInfos->totalRealised;
			    $global_nbRealised += $dateInfos->nbRealised;
			    $global_totalSigned += $dateInfos->totalSigned;
			    $global_nbSigned += $dateInfos->nbSigned;
			    
			}
			
			$global_transformationRatio = calcRatio($global_nbSigned, $global_nbRealised);
			
			
			
			print '<th class="border-left-heavy"  style="text-align:right;" >'.price($global_totalRealised).'</th>';
			print '<th class="border-left-light"  style="text-align:right;" >'.price($global_totalSigned).'</th>';
			print '<th class="border-left-light"  style="text-align:right;" >'.price($global_transformationRatio).'%</th>';
			print "</tr>";
			
			
			// CUMUL TOTAL LINE
			print '<tr  class="oddeven" >';
			print '<th style="text-align:right;" >'.$langs->trans('TotalCumule').'</th>';
			
			$global_totalRealised=0;
			$global_totalSigned=0;
			$global_nbRealised=0;
			$global_nbSigned=0;
			
			foreach ($TData['dates'] as  $dateKey => $dateInfos  ){
			    
			    
			    $global_totalRealised += $dateInfos->totalRealised;
			    $global_nbRealised += $dateInfos->nbRealised;
			    $global_totalSigned += $dateInfos->totalSigned;
			    $global_nbSigned += $dateInfos->nbSigned;
			    $global_transformationRatio = calcRatio($global_nbSigned, $global_nbRealised);
			    
			    
			    print '<th class="border-left-heavy"  style="text-align:right;" >'.price($global_totalRealised).'</th>';
			    print '<th class="border-left-light"  style="text-align:right;" >'.price($global_totalSigned).'</th>';
			    print '<th class="border-left-light"  style="text-align:right;" >'.price($global_transformationRatio).'%</th>';
			    
			}
			

			print '<th class="border-left-heavy" colspan="3" ></th>';
			
			print "</tr>";
			
			
			
			print '</tfoot>';
			print '</table>';
		
		
	}
	
	
	function calcRatio($nbSigned, $nbRealised){
	    $transformationRatio=0;
	    if(!empty($nbSigned))
	    {
	        $transformationRatio = $nbSigned / $nbRealised * 100;
	        $transformationRatio = round($transformationRatio,2);
	    }
	    
	    return $transformationRatio;
	}
	
	
	function insertBefore($input, $index, $newKey, $element) {
	    if (!array_key_exists($index, $input)) {
	        throw new Exception("Index not found");
	    }
	    $tmpArray = array();
	    foreach ($input as $key => $value) {
	        if ($key === $index) {
	            $tmpArray[$newKey] = $element;
	        }
	        $tmpArray[$key] = $value;
	    }
	    return $tmpArray;
	}
	
	function prepareTData(&$TData) {
	    $lastM=0;
	    foreach ($TData['dates'] as $dateKey =>& $dateInfos ){
	        
	        $curM = dol_print_date($dateInfos->time, '%m');
	        $curY = dol_print_date($dateInfos->time, '%Y');
	        
	        if(!empty($lastM) && $curM>$lastM+1){
	            for ($i = $lastM+1; $i < $curM; $i++) {
	                
	                $newCol = new stdClass();
	                $newCol->time = mktime(0,0,1,$i,1,$curY);
	                $newCol->totalRealised = 0;
	                $newCol->nbRealised = 0;
	                
	                $newCol->totalSigned = 0;
	                $newCol->nbSigned =0;
	                
	                $newCol->transormationRatio = 0;
	                
	                
	                $TData['dates'] = insertBefore($TData['dates'], $dateKey, $curY.'-'.$i, $newCol);
	                
	            }
	        }
	        
	        $dateInfos->totalRealised = 0;
	        $dateInfos->nbRealised = 0;
	        
	        $dateInfos->totalSigned = 0;
	        $dateInfos->nbSigned =0;
	        
	        $dateInfos->transormationRatio = 0;
	        
	        $lastM=dol_print_date($dateInfos->time, '%m');
	    }
	}
	
	
