<?php
/**
 * Deprecated Database class--DO NOT USE
 *
 * Do not use this class in any new code. Instead, use the PDODAO system in place. This deprecated class is still here
 * only for existing unit tests that use it. The production webapp no longer utilizes this class.
 *
 * @author Gina Trapani <ginatrapani[at]gmail[dot]com>
 *
 */
class Database {
    var $db_host;
    var $db_name;
    var $db_user;
    var $db_password;
    var $logger = null;
    var $table_prefix;
    var $slow_query_log_threshold = 2.0; //seconds
    var $GMT_offset=8;

    function __construct($THINKUP_CFG) {
        $this->db_host = $THINKUP_CFG['db_host'];
        $this->db_name = $THINKUP_CFG['db_name'];
        $this->db_user = $THINKUP_CFG['db_user'];
        $this->db_password = $THINKUP_CFG['db_password'];
        if (isset($THINKUP_CFG['table_prefix'])) {
            $this->table_prefix = $THINKUP_CFG['table_prefix'];
        }
        if (isset($THINKUP_CFG['GMT_offset'])) {
            $this->GMT_offset = $THINKUP_CFG['GMT_offset'];
        }

        if (isset($THINKUP_CFG['sql_log_location'])) {
            $this->logger = new LoggerSlowSQL($THINKUP_CFG['sql_log_location']);
            if (isset($THINKUP_CFG['slow_query_log_threshold'])) {
                $this->slow_query_log_threshold = $THINKUP_CFG['slow_query_log_threshold'];
            }

        }
    }

    function getConnection() {
        $fail = false;
        $conn = mysql_connect($this->db_host, $this->db_user, $this->db_password) or $fail = true;
        if ($fail) {
            throw new Exception("ERROR: ".mysql_error().$this->db_host.$this->db_user.$this->db_password);
        }
        mysql_select_db($this->db_name, $conn) or $fail = true;
        if ($fail) {
            throw new Exception("ERROR: ".mysql_errno()." ".mysql_error());
        }
        return $conn;
    }

    function closeConnection($conn) {
        mysql_close($conn);
        if ($this->logger != null ) {
            $this->logger->close();
        }
    }

    function exec($q) {
        $fail = false;
        $q = str_replace('#prefix#', $this->table_prefix, $q);
        $q = str_replace('#gmt_offset#', $this->GMT_offset, $q);

        //echo $q;
        $starttime = microtime(true);
        $r = mysql_query($q) or $fail = true;
        $endtime = microtime(true);
        $totaltime = $endtime - $starttime;
        if ( $totaltime >= $this->slow_query_log_threshold && $this->logger != null ){
            $this->logger->logQuery($q, $totaltime);
        }
        if ($fail){
            throw new Exception("ERROR: Query failed: ".$q." ".mysql_error());
        }
        return $r;
    }

}
?>
