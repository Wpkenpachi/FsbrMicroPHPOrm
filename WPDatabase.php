<?php
include 'Connect.php';

class WPDatabase {

    use Connect;

    /*
    *  @var $Instance = Instance of WPDatabase Object
    */
    private static $Instance;

    /*
    *  @var $Instance = Instance of PDO::Statement Connection
    */
    protected static $Db;

    /*
    * @var $Table
    */
    protected static $Table;

    /*
    * @var $Query
    */
    protected $Query;

    /*
    * @var $PreparedQuery
    */
    protected $PreparedQuery;

    /*
    * @var $Result
    */
    protected $Result;

    // ==================================================== //
    // ============= Variaveis de Operações =============== //
    // ==================================================== //
    protected $QueryString;
    protected $Inner;
    protected $Where;
    protected $Option;
    protected $OptionLater;




    /*
     *  @var $driver = Database Driver.
     *  @var $host = Host of database.
     *  @var $dbname = Database name.
     *  @var $user = User Database info username.
     *  @var $pass = User Database info password.
     */
    function __construct(){
        $Configs = $this->config();
        try{
            self::$Db = new PDO(
                $Configs['DB_DRIVER'] .':host='. $Configs['DB_HOST'] .';dbname='. $Configs['DB_NAME'].';',
                $Configs['DB_USER'],
                $Configs['DB_PASS']
            );
            self::$Db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }catch(PDOEXEPTION $e){
            echo "Error: " . $e->getMessage();
        }
    }

    public static function table($table){
        self::$Table = (string) $table;
        if(is_null(self::$Instance)){
            self::$Instance = new self();
        }

        return self::$Instance;
    }


    // ===================================================== //
    // ==== Query Type Methods ============================= //
    // ===================================================== //

    public function select($elems = null){
        // Setting Table Variable
        $table = self::$Table;

        if(is_string($elems)){

            $this->QueryString .= " SELECT {$elems} FROM {$table} ";

        }else if(is_array($elems)){
            
            $_elems_array = [];// array of fields

            $_elems_string = ''; // string with fields

            // walking in array, to buld query fields
            array_walk($elems, function($elem_value, $elem_key) use (&$_elems_string, &$_elems_array){
                
                foreach($elem_value as $value):
                    $_elems_array[] = $elem_key . '.' . $value;
                endforeach;

                $_elems_string = implode(',' , $_elems_array);
                
            });

            $this->QueryString .= "SELECT {$_elems_string} FROM {$table}";
        }elseif($elems == null){
            $this->QueryString .= "SELECT * FROM {$table}";
        }

        return $this;
    }


    public function create(array $data){
        $table = self::$Table;
        $result = $this->resolveCreateParams($data);
        $col = $result['col'];
        
        $val = $result['val'];

        $this->QueryString = "INSERT INTO {$table} ({$col}) VALUES ({$val})";

        return $this;
    }

    public function update(array $data){
        $table = self::$Table;
        $data = $this->resolveUpdateParams($data);
        $this->QueryString = "UPDATE {$table} SET {$data}";
        return $this;
    }

    public function delete(){
        $table = self::$Table;
        $this->QueryString = "DELETE FROM {$table}";
        return $this;
    }







    // ===================================================== //
    // ==== Conditional Methods ============================ //
    // ===================================================== //

    public function innerJoin($table, array $on){
        $table = (string) $table;
        $join = "INNER JOIN";

        // Default query string to INNER JOIN conditional
        $_string_query = $this->resolveJoin($table, $join, $on);

        $this->Inner .= $_string_query;
        return $this;
    }

    public function leftJoin($table, array $on){
        $table = (string) $table;
        $join = "LEFT JOIN";

        // Default query string to INNER JOIN conditional
        $_string_query = $this->resolveJoin($table, $join, $on);

        $this->Inner .= $_string_query;
        return $this;
    }

    public function rightJoin($table, array $on){
        $table = (string) $table;
        $join = "RIGHT JOIN";

        // Default query string to INNER JOIN conditional
        $_string_query = $this->resolveJoin($table, $join, $on);

        $this->Inner .= $_string_query;
        return $this;
    }

    public function fullJoin($table, array $on){
        $table = (string) $table;
        $join = "FULL OUTER JOIN";

        // Default query string to INNER JOIN conditional
        $_string_query = $this->resolveJoin($table, $join, $on);

        $this->Inner .= $_string_query;
        return $this;
    }

    public function where(){

        // Default query string to WHERE conditional
        $_string_query = " WHERE ";

        // Getting all params 
        $_params = func_get_args();

        $_string_query .= $this->resolveWhereParams( $_params );

        $this->Where .= " {$_string_query} ";
        return $this;
    }


    public function custom($where){
        $where = (string) $where;
        $this->Where .= " {$where} ";
        return $this;
    }



    // ===================================================== //
    // ==== Public OPTION Methods ========================== //
    // ===================================================== //

    // Order by needs to be before limit
    public function orderBy($order, array $by){
        $_order = (string) $order;
        $_params = implode($by);
        switch(strtoupper($_order)){
            case 'ASC':
                $_order = 'ASC';
                break;

            case 'DESC':
                $_order = 'DESC';
                break;

            default:
                $_order = 'ASC';
        }
        $this->Option .= " ORDER BY {$_params} {$_order}";

        return $this;
    }

    public function limit(int $limit){
        $this->OptionLater .= " LIMIT {$limit} ";
        return $this;
    }

    public function offset(int $offest){
        $this->OptionLater .= " OFFSET {$offset} ";
        return $this;
    }

    //
    // Where Options
    //
    public function like($pattern){
        $this->Where .= " LIKE '{$pattern}' ";
        return $this;
    }

    public function between($p1, $p2){
        if(is_string($p1)){
            $p1 = "'{$p1}'";
        }
        if(is_string($p2)){
            $p2 = "'{$p2}'";
        }
        $this->Where .= " BETWEEN {$p1} AND {$p2} ";
        return $this;
    }

    public function in(){
        $this->Where .= " IN ";
        return $this;
    }

    public function not(){
        $this->Where .= " NOT ";
        return $this;
    }

    public function and(){
        $this->Where .= " AND ";    
        return $this;
    }

    public function or(){
        $this->Where .= " OR ";    
        return $this;
    }

    public function isNotNull(){
        $this->Where .= " IS NOT NULL ";
        return $this;
    }

    public function isNull(){
        $this->Where .= " IS NULL ";    
        return $this;
    }


    // ===================================================== //
    // ==== Private Build Methods ========================== //
    // ===================================================== //
    private function resolveWhereParams($params = null){
        $_params = $params;
        $_result = '';

        if( count($_params) == 1 && is_string($_params[0]) ){
            $_result  = "{$_params['0']}";
        }elseif( count($_params) == 2 ){
            $_result = "{$_params['0']}" . " = " . "{$_params['1']}";
        }elseif( count($_params) == 3 ){
            $_result = "{$_params['0']}" . " {$_params['1']} " . "{$_params['2']}";
        }elseif( count($_params) > 3 ){
            echo "Much params...";
            die;
        }

        return $_result;
    }

    public function resolveJoin($table, $join){

        $on_array = [];
        $on_string = '';
        $on = func_get_arg(2);
        $this->on($on);
        
        // Default query string to INNER JOIN conditional
        $_string_query = " {$join} {$table} ON {$on} ";
        return $_string_query;
    }

    private function on(&$on){

        $_params = $on;
        $_resultado = [];

        array_walk($_params,function(&$_param) use (&$_resultado){
            if( count($_param) == 1 && is_string($_param) ){
                $_resultado[]  = "{$_param['0']}";
            }elseif( count($_param) == 2 ){
                $_resultado[] = "{$_param['0']}" . " = " . "{$_param['1']}";
            }elseif( count($_param) == 3 ){
                $_resultado[] = "{$_param['0']}" . " {$_param['1']} " . "{$_param['2']}";
            }elseif( count($_param) > 3 ){
                echo "Much params...";
                die;
            }
        });

        $on = implode(', ' , $_resultado);

        return $on;
    }

    
    private function resolveCreateParams(array $params){
        $_columns = implode(',' ,array_keys($params));
        $_values = array_values($params);
        array_walk($_values, function(&$_v){
            $_v = " '" . $_v .  "' ";
        });
        $_values = implode(', ' ,$_values);
        return ['col' => $_columns, 'val' => $_values];
    }

    private function resolveUpdateParams($params){
        $_array = [];
        $_string = '';
        array_walk($params, function(&$value, &$column) use (&$_array){
            if(is_string($value)){
                $_array[] = $column . '=' . "'{$value}'";
            }elseif(is_int($value)){
                $_array[] = $column . '=' . "{$value}";
            }
        });

        $_string = implode(', ' , $_array);

        return $_string;
    }

    
    // ===================================================== //
    // ==== METHOD FINAL BUILD QUERY ======================= //
    // ===================================================== //
    public function buildAll(){
        // Verify if the query string is empty or null
        if( !empty($this->QueryString) && !empty(self::$Table) ):

        $this->Query =  $this->QueryString . 
                        $this->Inner . 
                        $this->Where .
                        $this->Option .
                        $this->OptionLater;

        endif;

        // Building,Preparing and Executing Query
        $this->PreparedQuery = self::$Db->prepare($this->Query);
        $this->PreparedQuery->execute();
    }


    public function single($obj = null){
        $this->buildAll();
        $result = null;
        if($obj){
            $result = $this->PreparedQuery->fetch(PDO::FETCH_OBJ);
        }else{
            $result = $this->PreparedQuery->fetch(PDO::FETCH_ASSOC);
        }
        return $result;
    }

    public function all($obj = null){
        $this->buildAll();

        $result = null;
        if($obj != null){
            $result = $this->PreparedQuery->fetchAll(PDO::FETCH_OBJ);
        }else{
            $result = $this->PreparedQuery->fetchAll(PDO::FETCH_ASSOC);
        }
        return $result;
    }

    public function confirm(){
        $this->buildAll();
        return $this->PreparedQuery->rowCount();
    }

    public function lastId(){
        $this->buildAll();
        return self::$Db->lastInsertId();
    }



    // ===================================================== //
    // ==== Public RETURN Methods ========================== //
    // ===================================================== //
    

    public function debug(){
        echo $this->QueryString . 
             $this->Inner . 
             $this->Where .
             $this->Option .
             $this->OptionLater;
    }
}

// ================== Test =================== //

$table = 'table2';
$on = [ 
    ['table2.id', '>','table.name'] ];
WPDatabase::table('table')
            ->select()
            ->innerJoin($table, $on)
            ->debug();


