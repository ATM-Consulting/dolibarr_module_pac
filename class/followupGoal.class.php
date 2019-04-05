<?php 

/*
 * Class objectif du suivit commercial
 */
class followupGoal extends  CommonObject{
    
    public $element='followup_goal';
    public $table_element='followup_goal';
    
    
    
    public $fk_user = 0;
    public $year = 0;
    public $month= 0;
    public $amount=0;
    public $fk_cat=0;
    
    function __construct($db) {
        $this->db = $db;
    }
    
    
    function userSaveRight()
    {
        global $user;
        
        $saveRight = false;
        if($user->rights->pac->changeGoal)
        {
            $saveRight = true;
        }
        
        return $saveRight;
    }
    
    static function getAmount ($fk_user, $y , $m, $fk_cat=0){
        global $db, $conf;
        
        $followupGoal = new followupGoal($db);
        if($followupGoal->fetchFromDate($fk_user, $y , $m, $fk_cat))
        {
            return $followupGoal->amount;
        }
        elseif (!empty($fk_user) && empty($fk_cat)){
            // Récupération de la valeur par defaut
            return intval($conf->global->PAC_COMERCIAL_FOLLOWUP_DEFAULT_USER_GOAL);
        }
        elseif (empty($fk_user) && empty($fk_cat)){
            // Récupération de la valeur par defaut
            return intval($conf->global->PAC_COMERCIAL_FOLLOWUP_DEFAULT_GLOBAL_GOAL);
        }
    }
    
    
    function save($user,$notrigger=0)
    {
        
        if(!empty($this->id)){
            return $this->update($user);
        } else {
            return $this->create($user);
        }
        
        return 0;
    }
    
    function create($user,$notrigger=0)
    {
        if($this->userSaveRight())
        {
            $sql = "SELECT MAX(rowid) FROM ".MAIN_DB_PREFIX.$this->table_element ;
            $res = $this->db->query($sql);
            $last_id = $this->db->fetch_array($res);
            $last_id = $last_id[0]+1;
            $sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element;
            $sql .= " (rowid,fk_user,fk_cat,year,month,amount)  VALUES  (";
            $sql .= " ".$last_id.",".intval($this->fk_user).",".intval($this->fk_cat).",".intval($this->year).",".intval($this->month).",".(!empty($this->amount)?intval($this->amount):'NULL').")";
        
            $this->lastSql = $sql;
            $res = $this->db->query($sql);
            if(!empty($res )){
                return $last_id;
            } else {
                return -1;
            }
        }
        
        return 0;
        
    }
    
    function update($user,$notrigger=0)
    {
        
        if($this->userSaveRight())
        {
            
            $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET  ";
            $sql .= " fk_user=".intval($this->fk_user) ;
            $sql .= ", fk_cat=".intval($this->fk_cat) ;
            $sql .= ", year=".intval($this->year);
            $sql .= ", month=".intval($this->month);
            $sql .= ", amount=".intval($this->amount);
            $sql .= " WHERE rowid=".$this->id ;
       
                
            $this->lastSql = $sql;
            $res = $this->db->query($sql);
            if(!empty($res )){
                return 1;
            } else {
                return -1;
            }
        }
        
        return 0;
    }
    
    
    /**
     * Function fetchFromOrderDet
     *
     * 	@param		int		$rowid : contratdet rowid
     * 	@return		bool
     */
    function fetchFromDate($fk_user, $y , $m, $fk_cat=0){
        
        $sql ="SELECT * FROM ".MAIN_DB_PREFIX.$this->table_element;
        $sql.=" WHERE fk_user=".intval($fk_user);
        $sql.=" AND year=".intval($y);
        $sql.=" AND month=".intval($m);
        
        $res = $this->db->query($sql);
        if($res){
            if($result = $this->db->fetch_array($res))
            {
                $this->id = $result['rowid'];
                foreach( $result as $key => $value )
                {
                    $this->{$key} = $result[$key];
                }
                return true;
            }
        }
        
        
        return false;
        
    }
    
    /**
     * Function fetch
     *
     * 	@param		int		$rowid
     * 	@return		bool
     */
    function fetch($rowid){
        if(!empty($rowid)){
            $sql="SELECT * FROM ".MAIN_DB_PREFIX.$this->table_element." WHERE rowid=".$rowid;
            $res = $this->db->query($sql);
            if($res){
                $result = $this->db->fetch_array($res);
                $this->id = $result['rowid'];
                foreach( $result as $key => $value )
                {
                    $this->{$key} = $result[$key];
                }
                
                return true;
            }
        } else {
            
            return false;
        }
    }
    
}