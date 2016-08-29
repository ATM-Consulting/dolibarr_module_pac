<?php
	require('config.php');
	dol_include_once('/projet/class/project.class.php');
	dol_include_once('/projet/class/task.class.php');
	dol_include_once('/user/class/user.class.php');
	dol_include_once('/core/lib/usergroups.lib.php');
	dol_include_once('/comm/propal/class/propal.class.php');
	dol_include_once('/core/class/html.form.class.php');

		
	
	
	llxHeader('',$langs->trans('MontantPropales'));
	
	print dol_get_fiche_head($langs->trans('MontantPropales'));
	print_fiche_titre($langs->trans("MontantPropales"));
	
	
	_print_filtres();
	_print_rapport();
	
	function _get_propales($iduser, $date_deb, $date_fin){
		global $db;
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
		
		$sql = 'SELECT p.rowid , p.datec, p.ref, p.ref_client, p.fin_validite ,p.fk_soc
			FROM '.MAIN_DB_PREFIX.'propal p LEFT JOIN '.MAIN_DB_PREFIX.'societe_commerciaux sc ON (sc.fk_soc = p.fk_soc)
			WHERE sc.fk_user = '.$iduser.' 
			AND (p.'.$date_field.' BETWEEN "'.$date_deb.'" AND "'.$date_fin.'") 
			AND p.fk_statut='.$statut.' ';
			
		if (!empty($sortfield) && !empty($sortorder)){
			$sql .= 'ORDER BY '.$sortfield.' '.$sortorder.' ';
		}

		$resql = $db->query($sql);
		
		if ($resql){
			while ($line = $db->fetch_object($resql)){
				
				$TData[] = array(
					'idPropale' => $line->rowid
				);
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
		global $db, $langs;
		
		$idUser = GETPOST('userid');
		$user = new User($db);
		$user->fetch($idUser);
		
		$date_d=str_replace('/', '-', GETPOST('date_deb'));
		$date_f=str_replace('/', '-', GETPOST('date_fin'));
		$date_deb=date('Y-m-d', strtotime($date_d));
		$date_fin=date('Y-m-d', strtotime($date_f));
			
		if(GETPOST('date_deb')=='')$date_deb=date('Y-m-d' , strtotime(date('Y-m-d'))-(60*60*24*30));
		if(GETPOST('date_fin')=='')$date_fin=date('Y-m-d');
		$param = '&userid='.$idUser.'&date_deb='.$date_deb.'&date_fin='.$date_fins;
		if(!empty($idUser)){
			$TData = _get_propales($idUser, $date_deb, $date_fin);
		
			print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">';
		
			print '<tr class="liste_titre">';
			print_liste_field_titre($langs->trans('Ref'),$_SERVER["PHP_SELF"],'p.ref','',$param,'',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans('RefCustomer'),$_SERVER["PHP_SELF"],'p.ref_client','',$param,'',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans('Company'),$_SERVER["PHP_SELF"],'s.nom','',$param,'',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans('Town'),$_SERVER["PHP_SELF"],'s.town','',$param,'',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans('Date'),$_SERVER["PHP_SELF"],'p.datec','',$param, 'align="center"',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans('DateEndPropalShort'),$_SERVER["PHP_SELF"],'p.fin_validite','',$param, 'align="center"',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans('AmountHT'),$_SERVER["PHP_SELF"],'p.total_ht','',$param, 'align="right"',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans('CompanySalesman'),$_SERVER["PHP_SELF"],'u.login','',$param,'align="center"',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans('Status'),$_SERVER["PHP_SELF"],'p.fk_statut','',$param,'align="right"',$sortfield,$sortorder);
			print_liste_field_titre('',$_SERVER["PHP_SELF"],"",'','','',$sortfield,$sortorder,'maxwidthsearch ');
			print "</tr>";

			$var=true;
			$total=0;
			$subtotal=0;
		
			foreach ($TData as $data)
			{
				$propal = new Propal($db);
				$propal->fetch($data['idPropale']);
				
				$societe = new Societe($db);
				$societe->fetch($propal->socid);
					
				print '<tr '.$bc[$var].'>';
				print '<td class="nowrap">';
		
				print '<table class="nobordernopadding"><tr class="nocellnopadd">';

		
				// Ref
				print '<td width="16" align="right" class="nobordernopadding hideonsmartphone">';
				print $propal->getNomUrl(1);
				print '</td></tr></table>';
		
				print "</td>";
		
				// Customer ref
				print '<td class="nocellnopadd nowrap">';
				print $propal->ref_client;
				print '</td>';
		
				$url = DOL_URL_ROOT.'/comm/card.php?socid='.$objp->rowid;
		
				// Company
				print '<td>';
				print $societe->getNomUrl(1,'customer');
				print '</td>';
		
				// Town
				print '<td class="nocellnopadd">';
				print $societe->town;
				print '</td>';
		
				// Date proposal
				print '<td align="center">';
				print dol_print_date($propal->date_creation, 'day');
				print "</td>";
				
				//Date fin validité
				print '<td align="center">'.dol_print_date($propal->fin_validite, 'day');
				print '</td>';
				

				print '<td align="right">'.price($propal->total_ht)."</td>";
		
				print '<td align="center">';
				print $user->getLoginUrl(1);
				print "</td>";		
				print '<td align="right">'.$propal->LibStatut($propal->statut, 2).'</td>';		
				print "</tr>";
		
				$total += $propal->total_ht;
		
				$i++;
			}
		
			if ($total>0)
					{
						$var=!$var;
						print '<tr class="liste_total"><td align="left">'.$langs->trans("TotalHT").'</td>';
						print '<td colspan="6" align="right">'.price($total).'</td><td colspan="3"></td>';
						print '</tr>';
					}
		
			print '</table>';
		}
		
	}
