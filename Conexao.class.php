<?php
//Conexao.class.php
class Conexao {
    private $pdo;
    private $d = ['localhost','jokenpo','root','','utf8'];
    
    public function __construct() {
        try {
            $str = "mysql:host={$this->d[0]};dbname={$this->d[1]};charset={$this->d[4]}";
            $this->pdo = new PDO($str, $this->d[2], $this->d[3]);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // echo "Conectado com sucesso!";
        } catch (PDOException $e) {
            echo "Conexão falhou: " . $e->getMessage();
        }
    }

    public function getConn() {
        return $this->pdo;
    }
}
?>