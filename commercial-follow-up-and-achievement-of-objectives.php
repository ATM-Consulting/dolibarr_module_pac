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
	
	
	
	llxFooter();
	
	
	
	
	
	
	function _get_propales($iduser, $date_deb, $date_fin){
	    global $db,$sortorder,$sortfield, $conf;
		$sortfield = GETPOST('sortfield');
		$sortorder = GETPOST('sortorder');
		$TData = array();
		
		
		$catLists = getCategoryChild($conf->global->PAC_COMERCIAL_FOLLOWUP_PARENT_CAT);
		

		
		// A mon avis il faut 3 requête avec un UNION :
		// Pour l'instant le montant réalisé se met dans le mois de cloture si la propale est close.
		
	    // - Toutes les propales (fk_statut > 0) sur datep
	    _get_propales_query($TData, $catLists, $iduser, $date_deb, $date_fin, 'all');
	    
		
		// - Toutes les propales signée (fk_statut IN (2,4)) sur date_cloture (ou date_signature à revoir)
		_get_propales_query($TData, $catLists, $iduser, $date_deb, $date_fin, 'allSigned');
		    
		    
		// - Toutes les propales non signée (fk_statut = 3) sur date_cloture (ou date_signature à revoir)
        _get_propales_query($TData, $catLists, $iduser, $date_deb, $date_fin, 'allNotSigned');
		   
		
       
		
		return $TData;
	}
	
	
	
	function prepareTDataFromSql ($sql, &$TData, $type, $forceNull = false){
	    global $db;
	    
	    $resql = $db->query($sql);
	    //print '<br><br>'.$sql;
	    if ($resql){
	        while ($line = $db->fetch_object($resql)){
	            // dispatch results
	            
	            if(empty($line->fk_categorie)){ $line->fk_categorie = 0; } // in NULL case
	            
	            if($forceNull){ $line->fk_categorie = 0; } // in forceCatId case
	            
	            $line->time = strtotime($line->year.'-'.$line->month.'-01 00:00:00'); // utilisé plus tard juste poour un affichage propre des dates
	            
	            $TData['data'][$line->fk_categorie][$line->year.'-'.$line->month][$type] = $line ; // les résultats brute
	            $TData['dates'][$line->year.'-'.$line->month] = new stdClass();// liste des mois
	            $TData['dates'][$line->year.'-'.$line->month]->time = $line->time; // utilisé plus tard juste poour un affichage propre des dates
	            
	        }
	    }
	}
	
	function prepareTDataFromSqlHorsCat ($sql, &$TData, $type){
	    global $db;
	    
	    $resql = $db->query($sql);
	    //print '<br><br>'.$sql;
	    if ($resql){
	        while ($line = $db->fetch_object($resql)){
	            // dispatch results
	            
	            if(empty($line->fk_categorie)){ $line->fk_categorie = 0; } // in NULL case
	            
	            $line->fk_categorie = 0;
	            
	            $line->time = strtotime($line->year.'-'.$line->month.'-01 00:00:00'); // utilisé plus tard juste poour un affichage propre des dates
	            
	            $TData['data'][$line->fk_categorie][$line->year.'-'.$line->month][$type] = $line ; // les résultats brute
	            $TData['dates'][$line->year.'-'.$line->month] = new stdClass();// liste des mois
	            $TData['dates'][$line->year.'-'.$line->month]->time = $line->time; // utilisé plus tard juste poour un affichage propre des dates
	            
	        }
	    }
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
	    global $db, $langs,$sortorder,$sortfield,$form;
		
		$idUser=GETPOST('userid');
		$date_d=str_replace('/', '-', GETPOST('date_deb'));
		$date_f=str_replace('/', '-', GETPOST('date_fin'));
		$date_deb=date('Y-m-01', strtotime($date_d));
		$date_fin=date('Y-m-t', strtotime($date_f));
			
		if(GETPOST('date_deb')=='')$date_deb=date('Y-m-01' , strtotime(date('Y-m-01'))-(60*60*24*30));
		if(GETPOST('date_fin')=='')$date_fin=date('Y-m-t');
		
		
		/*$dateSignaturesearchField = 'search_options_'.'date_signature' ;
		$param = '&search_month='.date('m',strtotime($date_deb)).'&search_year='.date('Y',strtotime($date_deb)).$dateSignaturesearchField.'=';
		$listLink = dol_buildpath('comm/propal/list.php', 2) . '?formfilteraction=list&action=list&sortfield=p.ref&sortorder=desc'.$param;*/
		
		
			$TData = _get_propales($idUser, $date_deb, $date_fin);
		
			if(empty($TData)) return ;
			
			// INIT DATE INFOS TOTALS
			prepareTData($TData);
			
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
			    
			    $sector_totalNotSigned = 0;
			    $sector_nbNotSigned =0;
			    
			    foreach ($TData['dates'] as $dateKey => $dateInfos ){
			        
			        $totalRealised = 0;
			        $nbRealised = 0;
			        
			        $totalSigned = 0;
			        $nbSigned = 0;
			        
			        $totalNotSigned = 0;
			        $nbNotSigned = 0;
					
			        $transformationRatio = 0;
			        
			        
			        // AFFECTATION DES TOTAUX AUX DATES
			        if(!empty($data[$dateKey]))
			        {
			            foreach ($data[$dateKey] as $type => $object)
			            {
			                if($type == 'all'){
			                    // nothing
			                    $totalRealised += $object->total_ht;
			                    $nbRealised += $object->totalcount;
			                }
			                elseif($type == 'allSigned'){
			                    $totalSigned += $object->total_ht;
			                    $nbSigned += $object->totalcount;
			                }
			                elseif($type == 'allNotSigned'){
								
			                    $totalNotSigned += $object->total_ht;
			                    $nbNotSigned += $object->totalcount;
			                }
			                
			            }
			        }
			        
			        // taux de transformation montant
			        $ratioDetails = $langs->trans('Amount').' : '.price($totalSigned).' '.$langs->trans('Signed').' / ('.  price($totalSigned) .' '.$langs->trans('Signed').' + '. price($totalNotSigned) .' '.$langs->trans('NotSigned').')';
			        $transformationRatio = calcRatio($totalSigned, $totalSigned + $totalNotSigned);
			        $ratioDetails .= ' = '.$transformationRatio . '%';
			        
			        // taux de transformation qty
			        $ratioDetails .= '<br/>'.$langs->trans('Number').' : '.$nbSigned.' '.$langs->trans('Signed').' / ('.  $nbSigned .' '.$langs->trans('Signed').' + '. $nbNotSigned .' '.$langs->trans('NotSigned').')';
			        $ratioDetails .= ' = '.calcRatio($nbSigned, $nbSigned + $nbNotSigned) . '%';
			        //$transformationRatio = calcRatio($nbSigned, $nbSigned + $nbNotSigned);
			        
			        print '<td class="border-left-heavy totalRealised"  style="text-align:right;" >'.price($totalRealised).'</td>'; //<a target="_blank" href="'.$listLink.'" >'.price($totalRealised).'</a>
			        print '<td class="border-left-light totalSigned"  style="text-align:right;" >'.price($totalSigned).'</td>'; // <a href="'.$listLink.'&propal_statut=2,3,4" >'.price($totalSigned).'</a>
			        print '<td class="border-left-light transformationRatio"  style="text-align:right;" >'.$form->textwithtooltip(price($transformationRatio).'%', $ratioDetails, 3).'</td>';
			        
			        
			        
			        
			        
			        // Mise à jour des totaux secteur (ligne)
			        $sector_totalRealised += $totalRealised;
			        $sector_nbRealised += $nbRealised;
			        
			        $sector_totalSigned += $totalSigned;
			        $sector_nbSigned += $nbSigned;
			        
			        $sector_totalNotSigned += $totalNotSigned;
			        $sector_nbNotSigned += $nbNotSigned;
			        
			        // Mise à jour des totaux du bloc date
			        $TData['dates'][$dateKey]->totalRealised += $totalRealised;
			        $TData['dates'][$dateKey]->nbRealised += $nbRealised;
			        
			        $TData['dates'][$dateKey]->totalSigned += $totalSigned;
			        $TData['dates'][$dateKey]->nbSigned += $nbSigned;
			        
			        $TData['dates'][$dateKey]->totalNotSigned += $totalNotSigned;
			        $TData['dates'][$dateKey]->nbNotSigned += $nbNotSigned;
			       
			    }
			    
			    
			    
			    // taux de transformation montant
			    $ratioDetails = $langs->trans('Amount').' : '.price($sector_totalSigned).' '.$langs->trans('Signed').' / ('.  price($sector_totalSigned) .' '.$langs->trans('Signed').' + '. price($sector_totalNotSigned) .' '.$langs->trans('NotSigned').')';
			    $sector_transformationRatio = calcRatio($sector_totalSigned, $sector_totalSigned + $sector_totalNotSigned);
			    $ratioDetails .= ' = '.$sector_transformationRatio . '%';
			    
			    // taux de transformation qty
			    $ratioDetails .= '<br/>'.$langs->trans('Number').' : '.$langs->trans('Signed').' / ('.  $sector_nbSigned .' '.$langs->trans('Signed').' + '. $sector_nbNotSigned .' '.$langs->trans('NotSigned').')';
			    $ratioDetails .= ' = '.calcRatio($sector_nbSigned, $sector_nbSigned + $sector_nbNotSigned).'%';
			    //$transformationRatio = calcRatio($sector_nbSigned, $sector_nbSigned + $sector_nbNotSigned);
			    
			    
			    
			    
			    print '<td class="border-left-heavy sector_totalRealised"  style="text-align:right;" >'.price($sector_totalRealised).'</td>';
			    print '<td class="border-left-light sector_totalSigned"  style="text-align:right;" >'.price($sector_totalSigned).'</td>';
			    print '<td class="border-left-light sector_transformationRatio"  style="text-align:right;" >'.$form->textwithtooltip(price($sector_transformationRatio).'%', $ratioDetails, 3).'</td>';
			    print '</tr>';
			    
			}
			print '</tbody>';
			
			
			
			print '<tfoot>';
			
			// TOTAL LINE
			print '<tr  class="oddeven" >';
			print '<th style="text-align:right;" >'.$langs->trans('Total').'</th>';
			
			
			$global_totalRealised=0;
			$global_nbRealised=0;
			$global_totalSigned=0;
			$global_nbSigned=0;
			$global_totalNotSigned=0;
			$global_nbNotSigned=0;
			
			foreach ($TData['dates'] as  $dateKey => $dateInfos  ){
			    
			    
			    
			    
			    // taux de transformation montant
			    $ratioDetails = $langs->trans('Amount').' : '.price($dateInfos->totalSigned).' '.$langs->trans('Signed').' / ('.  price($dateInfos->totalSigned) .' '.$langs->trans('Signed').' + '. price($dateInfos->totalNotSigned) .' '.$langs->trans('NotSigned').')';
			    $dateInfos->transformationRatio = calcRatio($dateInfos->totalSigned, $dateInfos->totalSigned + $dateInfos->totalNotSigned);
			    $ratioDetails .= ' = '.$dateInfos->transformationRatio . '%';
			    
			    // taux de transformation qty
			    $ratioDetails .= '<br/>'.$langs->trans('Number').' : '.$dateInfos->nbSigned.' '.$langs->trans('Signed').' / ('.  $dateInfos->nbSigned .' '.$langs->trans('Signed').' + '. $dateInfos->nbNotSigned .' '.$langs->trans('NotSigned').')';
			    $ratioDetails .= ' = '.calcRatio($dateInfos->nbSigned, $dateInfos->nbSigned + $dateInfos->nbNotSigned). '%';
			    //$dateInfos->transformationRatio = calcRatio($dateInfos->nbSigned, $dateInfos->nbSigned + $dateInfos->nbNotSigned);
			    
			    
			    print '<th class="border-left-heavy"  style="text-align:right;" >'.price($dateInfos->totalRealised).'</th>';
			    print '<th class="border-left-light"  style="text-align:right;" >'.price($dateInfos->totalSigned).'</th>';
			    print '<th class="border-left-light"  style="text-align:right;" >'.$form->textwithtooltip(price($dateInfos->transformationRatio).'%', $ratioDetails, 3).'</th>';
			    
			    $global_totalRealised += $dateInfos->totalRealised;
			    $global_nbRealised += $dateInfos->nbRealised;
			    $global_totalSigned += $dateInfos->totalSigned;
			    $global_nbSigned += $dateInfos->nbSigned;
			    $global_totalNotSigned += $dateInfos->totalNotSigned;
			    $global_nbNotSigned += $dateInfos->nbNotSigned;
			    
			}
			
			
			// taux de transformation montant
			$ratioDetails = $langs->trans('Amount').' : '.price($global_totalSigned).' '.$langs->trans('Signed').' / ('.  price($global_totalSigned) .' '.$langs->trans('Signed').' + '. price($global_totalNotSigned) .' '.$langs->trans('NotSigned').')';
			$global_transformationRatio = calcRatio($global_totalSigned, $global_totalSigned + $global_totalNotSigned);
			$ratioDetails .= ' = '.$global_transformationRatio . '%';
			
			// taux de transformation qty
			$ratioDetails .= '<br/>'.$langs->trans('Number').' : '.$langs->trans('Signed').' / ('.  $global_nbSigned .' '.$langs->trans('Signed').' + '. $global_nbNotSigned .' '.$langs->trans('NotSigned').')';
			$ratioDetails .= ' = '.calcRatio($global_nbSigned, $global_nbSigned + $global_nbNotSigned) . '%';
			//$$global_transformationRatio = calcRatio($global_nbSigned, $global_nbSigned + $global_nbNotSigned);
			

			print '<th class="border-left-heavy"  style="text-align:right;" >'.price($global_totalRealised).'</th>';
			print '<th class="border-left-light"  style="text-align:right;" >'.price($global_totalSigned).'</th>';
			print '<th class="border-left-light"  style="text-align:right;"  >'.$form->textwithtooltip(price($global_transformationRatio).'%', $ratioDetails, 3).'</th>';
			print "</tr>";
			
			
			// CUMUL TOTAL LINE
			print '<tr  class="oddeven" >';
			print '<th style="text-align:right;" >'.$langs->trans('TotalCumule').'</th>';
			
			$global_totalRealised=0;
			$global_nbRealised=0;
			$global_totalSigned=0;
			$global_nbSigned=0;
			$global_totalNotSigned=0;
			$global_nbNotSigned=0;
			
			foreach ($TData['dates'] as  $dateKey => $dateInfos  ){
			    
			    
			    $global_totalRealised += $dateInfos->totalRealised;
			    $global_nbRealised += $dateInfos->nbRealised;
			    $global_totalSigned += $dateInfos->totalSigned;
			    $global_nbSigned += $dateInfos->nbSigned;
			    $global_totalNotSigned += $dateInfos->totalNotSigned;
			    $global_nbNotSigned += $dateInfos->nbNotSigned;
			    $global_transformationRatio = calcRatio($global_nbSigned, $global_nbSigned + $global_nbNotSigned);
			    
			    
			    // taux de transformation montant
			    $ratioDetails = $langs->trans('Amount').' : '.price($global_totalSigned).' '.$langs->trans('Signed').' / ('.  price($global_totalSigned) .' '.$langs->trans('Signed').' + '. price($global_totalNotSigned) .' '.$langs->trans('NotSigned').')';
			    $global_transformationRatio = calcRatio($global_totalSigned, $global_totalSigned + $global_totalNotSigned);
			    $ratioDetails .= ' = '.$global_transformationRatio . '%';
			    
			    // taux de transformation qty
			    $ratioDetails .= '<br/>'.$langs->trans('Number').' : '.$langs->trans('Signed').' / ('.  $global_nbSigned .' '.$langs->trans('Signed').' + '. $global_nbNotSigned .' '.$langs->trans('NotSigned').')';
			    $ratioDetails .= ' = '.calcRatio($global_nbSigned, $global_nbSigned + $global_nbNotSigned). '%';
			    //$$global_transformationRatio = calcRatio($global_nbSigned, $global_nbSigned + $global_nbNotSigned);
			    
			    print '<th class="border-left-heavy"  style="text-align:right;" >'.price($global_totalRealised).'</th>';
			    print '<th class="border-left-light"  style="text-align:right;" >'.price($global_totalSigned).'</th>';
			    print '<th class="border-left-light"  style="text-align:right;" >'.$form->textwithtooltip(price($global_transformationRatio).'%', $ratioDetails, 3).'</th>';
			    
			}
			

			print '<th class="border-left-heavy" colspan="3" ></th>';
			
			print "</tr>";
			
			
			
			print '</tfoot>';
			print '</table>';
		
		
	}
	
	
	function calcRatio($nbSigned, $nbRealised){
	    $transformationRatio=0;
	    
	    if(empty($nbRealised)){
	        return 0; // prevent division by 0
	    }
	    
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
	                
	                $newCol->transformationRatio = 0;
	                
	                
	                $TData['dates'] = insertBefore($TData['dates'], $dateKey, $curY.'-'.$i, $newCol);
	                
	            }
	        }
	        
	        $dateInfos->totalRealised = 0;
	        $dateInfos->nbRealised = 0;
	        
	        $dateInfos->totalSigned = 0;
	        $dateInfos->nbSigned =0;
	        
	        $dateInfos->transformationRatio = 0;
	        
	        $lastM=dol_print_date($dateInfos->time, '%m');
	    }
	}
	
	
	function getCategoryChild($cat,$deep=0)
	{
	    global $db;
	    
	    dol_include_once('categories/class/categorie.class.php');
	    
	    $Tlist = array();
	    
	    $category = new Categorie($db);
	    $res = $category->fetch($cat);
	    
	    $Tfilles = $category->get_filles();
	    if(!empty($Tfilles) && $Tfilles>0)
	    {
	        foreach ($Tfilles as &$fille)
	        {
	            $Tlist[] = $fille->id;
	            
	            $Tchild = getCategoryChild($fille->id,$deep++);
	            if(!empty($Tchild)){
	                $Tlist = array_merge($Tlist,$Tchild);
	            }
	        }
	    }
	    
	    return $Tlist;
	    
	}
	
	
	function _get_propales_query(&$TData, $catLists, $iduser, $date_deb, $date_fin, $type = 'all'){
	    global $db, $conf;
	    //$conf->global->PAC_COMERCIAL_FOLLOWUP_PARENT_CAT = 0;
	    
	    $date_deb = $date_fin = "2018-06-18";
	    
	    // A mon avis il faut 3 requête avec un UNION :
	    if($type=='all'){
	        // - Toutes les propales (fk_statut > 0) sur datep
	        $date_field ='p.datep';
	        $sqlStatus = ' AND p.fk_statut > 0 ';
	        
	    }
	    elseif($type=='allSigned'){
	        // - Toutes les propales signée (fk_statut IN (2,4)) sur date_cloture (ou date_signature à revoir)
	        $date_field ='p.date_cloture';
	        $sqlStatus = ' AND p.fk_statut IN (2,4) ';
	        
	    }
	    elseif($type=='allNotSigned'){
	        // - Toutes les propales non signée (fk_statut = 3) sur date_cloture (ou date_signature à revoir)
	        $date_field ='p.date_cloture';
	        $sqlStatus = ' AND p.fk_statut = 3 ';
	    }
	    else 
	    {
	        return false;
	    }
	    
	    // Pour l'instant le montant réalisé se met dans le mois de cloture si la propale est close.propal
	    
	    
	    $sqlSelect = 'SELECT';
	    $sqlSelect.= '	SUM(p.total_ht) total_ht,';
	    $sqlSelect.= '	count(p.rowid) totalcount,';
	    $sqlSelect.= '	MONTH('.$date_field.') month,';
	    $sqlSelect.= '	YEAR('.$date_field.') year';
		
		$sqlFrom= ' FROM '.MAIN_DB_PREFIX.'propal p';
		
		$sqlJoin ='';
		if($iduser>0) $sqlJoin.= ' LEFT OUTER JOIN '.MAIN_DB_PREFIX.'element_contact ec ON (ec.element_id = p.rowid)';
		
		
		$sqlWhere= ' WHERE 1 ';
	    
		if($iduser>0) $sqlWhere.= ' AND ec.fk_c_type_contact = 31 AND ec.fk_socpeople = '.$iduser;
		
		$sqlWhere.= ' AND ('.$date_field.' BETWEEN "'.$date_deb.'" AND "'.$date_fin.'") ';
	    
		$sqlWhere.= $sqlStatus;
		
		$sqlGroup = ' GROUP BY ';
		$sqlGroup.= ' MONTH('.$date_field.'), YEAR('.$date_field.') ';
	    
	    $sqlOrder .= ' ORDER BY '.$date_field.' ASC';
	    
	    
	    // prepare with parent cat
	    if(!empty($conf->global->PAC_COMERCIAL_FOLLOWUP_PARENT_CAT) && !empty($catLists)){
	        
	        $sql = $sqlSelect.' , cs.fk_categorie fk_categorie ';
	        $sql.= $sqlFrom;
	        $sql.= $sqlJoin.' LEFT OUTER JOIN '.MAIN_DB_PREFIX.'categorie_societe cs ON (cs.fk_soc = p.fk_soc) ';
	        $sql.= $sqlWhere;
	        $sql.= $sqlCatFilter.' AND cs.fk_categorie IN ('.implode(',', $catLists).') ';
	        $sql.= $sqlGroup.' , cs.fk_categorie  ';
	        $sql.= $sqlOrder;
	    }
	    else {
	        $sql = $sqlSelect.$sqlFrom.$sqlJoin.$sqlWhere.$sqlCatFilter.$sqlGroup.$sqlOrder;
	    }
	    
	    prepareTDataFromSql ($sql, $TData, $type);
	    
	    
	    // cherche et prepare les tiers hors catégorie
	    if(!empty($conf->global->PAC_COMERCIAL_FOLLOWUP_PARENT_CAT)){
	        
	        $sql = $sqlSelect;
	        $sql.= $sqlFrom;
	        $sql.= $sqlJoin;
	        $sql.= $sqlWhere;
	        $sql.= $sqlCatFilter.' AND p.fk_soc NOT IN (SELECT sqcs.fk_soc FROM  '.MAIN_DB_PREFIX.'categorie_societe sqcs WHERE  sqcs.fk_categorie IN ('.implode(',', $catLists).') )';
	        $sql.= $sqlGroup;
	        $sql.= $sqlOrder;
	        
	        
	        prepareTDataFromSqlHorsCat ($sql, $TData, $type, 1);
	    }
	    
	    
	    
	    return $TData;
	}
