<?php
//Jokenpo.class.php
class Player{
	//-> Atributos
	private $usuario = NULL;
	private $uuid    = NULL;
	private $conn    = NULL;
	private $logado  = false;
	
	function __construct() {
		$conn = new Conexao();
		$this->conn	= $conn->getConn();	
        $this->verificaLogin();
    }
	
	//-> Verifica se esta logado
	private function verificaLogin(){
		if(isset($_SESSION['usuario'])){
			if(($_SESSION['usuario'] != "")){
				$this->usuario = $_SESSION['usuario'];
				$this->uuid = $_SESSION['uuid'];
				$logado = true;
			}
		}
	}
	
	public function getLogado(){
		return $this->logado;
	}
	
	public function fazerLogin($u,$s){
	echo "senha:$s";		
		try {
			$stmt = $this->conn->prepare("SELECT * FROM usuario WHERE usuario = :u");
			$stmt->execute(["u" => $u]);
			$dados = $stmt->fetchAll();
			
			if(count($dados)){
				if(password_verify($s,$dados[0]['senha'])){
					
					$_SESSION['usuario'] = $u;
					$_SESSION['uuid'] = $dados[0]['uuid'];
					header("location:index.php");
				}else{
					die("Erro ao Logar");
				}
			}else{
				die("Erro no Login");
			}
		} catch (PDOException $e) {
			die("Erro" . $e->getMessage());
		}
	}
	
	public function fazerLogout(){
		unset($_SESSION['user_id']);
		unset($_SESSION['username']);
		session_destroy();
		header("location:index.php");
	}
}
?>