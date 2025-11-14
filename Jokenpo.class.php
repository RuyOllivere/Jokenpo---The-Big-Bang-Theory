<?php
//Jokenpo.class.php
class Jokenpo{
	//-> Atributos
	private $opcoes      = Array("Pedra" , "Papel" , "Tesoura");
	private $jogador     = 0;
	private $sala        = NULL;
	private $partida     = NULL;	
	private $usuario     = NULL;
	private $usuarioUUID = NULL;
	private $conn        = NULL;
	
	function __construct() {
		$conn = new Conexao();
		$this->conn	= $conn->getConn();	
		$this->getPlayer();        
    }
	
	private function validaRodada($p1 , $p2){
		if ($p1  == $p2 ){
			return "Empate";
		}elseif($p1  == 0 && $p2  == 2){
			return "Player 1 ganhou!";
		}elseif($p1 == 2 && $p2  == 0){
			return "Player 2 ganhou!";
		}elseif($p1 > $p2 ){
			$this->addGanhador(1);
			return "Player 1 ganhou!";			
		}else{
			$this->addGanhador(2);			
			return "Player 2 ganhou!";			
		}
	}
	
	//-> Verifica player
	private function getPlayer(){		
		if(!isset($_SESSION['usuario'])){
			echo "<link rel='stylesheet' href='style.css'>";
			echo '<form method="post">';
			echo '<input type="text" name="user" placeholder="Usuário"><br>';
			echo '<input type="password" name="pass" placeholder="Senha"><br>';
			echo '<input type="submit" value="Entrar">';
			echo '</form>';
			$this->sala = null;
		}else{
			$this->usuario = $_SESSION['usuario'];		
			$this->usuarioUUID = $_SESSION['uuid'];
			$this->verificaSala();
		}		
	}
	
	//-> Verifica se foi selecionada uma sala
	private function verificaSala(){
		if(isset($_SESSION['sala'])){
			if(($_SESSION['sala'] != "")){
				$this->sala = $_SESSION['sala'];				
				$this->jogador = $_SESSION['jogador'];
			}
			if($_SESSION['partida'] != ""){
				$this->partida = $_SESSION['partida'];
			}else{
				$stmt = $this->conn->prepare("SELECT * FROM partidas WHERE idSala = :id AND ganhador IS NULL");
				$stmt->execute(["id" => $this->sala]);
				$dados = $stmt->fetchAll();
				
				if(count($dados)){
					$_SESSION['partida'] = $dados[0]['id'];
					$this->partida = $dados[0]['id'];
				}else{
					echo "Partida não encontrada";
				}
			}
		}
	}
	
	public function entrarSala($s){	
		try {
			$stmt = $this->conn->prepare("SELECT * FROM sala WHERE uuid = :id AND idJogador1 = :u OR idJogador2 = :u OR idJogador2 IS NULL ");
			$stmt->execute(["id" => $s, "u" => $this->usuarioUUID]);
			$dados = $stmt->fetchAll();
			
			if(count($dados)){				
				if($dados[0]['idJogador1'] == $this->usuarioUUID){
					$_SESSION['jogador'] = "Jogador1";
				}else if($dados[0]['idJogador2'] == $this->usuarioUUID){
					$_SESSION['jogador'] = "Jogador2";
				}else if($dados[0]['idJogador2'] === NULL){
					$stmt = $this->conn->prepare("UPDATE sala SET idJogador2 = :u WHERE uuid = :id");
					$stmt->execute(["u" => $this->usuarioUUID, "id" => $s]);
					$_SESSION['jogador'] = "Jogador2";
				}else{
					$_SESSION['sala'] = "";
					$_SESSION['partida'] = "";
					$_SESSION['jogador'] = "";
					header("location: index.php");
				}			
			
				$this->sala = $s;
				$_SESSION['sala'] = $s;
				
				$stmt = $this->conn->prepare("SELECT * FROM partidas WHERE idSala = :id AND ganhador IS NULL");
				$stmt->execute(["id" => $s]);
				$dados = $stmt->fetchAll();
				
				if(count($dados)){
					$_SESSION['partida'] = $dados[0]['id'];
					$this->partida = $dados[0]['id'];
				}else{
					$stmt = $this->conn->prepare("INSERT INTO partidas (idSala) VALUES (:s)");
					$stmt->execute(["s" => $this->sala]);
					
					$_SESSION['partida'] = $this->conn->lastInsertId();
					$this->partida = $this->conn->lastInsertId();
				}			
				
				header("location: index.php");
			}else{
				echo "Não tem permissão nesta sala<hr>";
			}
		} catch (PDOException $e) {
			echo "Erro" . $e->getMessage();
		}
	}
	
	//-> Sai da sala
	public function sairSala(){		
		$_SESSION['sala'] = null;
		$_SESSION['partida'] = null;
		$this->sala = null;
		$this->partida = null;
		header('location: index.php');
	}
	
	//-> Efetua jogada
	public function fazerJogada($j){
		try {
			$stmt = $this->conn->prepare('SELECT * FROM sala WHERE uuid = :uuid AND idJogador1 = :id OR idJogador2 = :id');
			$stmt->execute(["uuid" => $this->sala , "id" => $this->usuarioUUID]);
			$dados = $stmt->fetchAll();
			if(count($dados)){
				if($dados[0]['idJogador1'] == $this->usuarioUUID){
					$sql = "UPDATE partidas SET j1Escolha = :j WHERE idSala = :uuid";
				}else{
					$sql ="UPDATE partidas SET j2Escolha = :j WHERE idSala = :uuid";
				}
				$stmt = $this->conn->prepare($sql);
				$stmt->execute(["j" => $j , "uuid" => $this->sala]);
				header('location: index.php');
			}else{
				echo "<br>Erro ao fazer jogada.<br>";
			}
		} catch (PDOException $e) {
			echo "Erro" . $e->getMessage();
		}
	}
	
	//-> Nova rodada
	public function novaRodada($p){
		if($p == 0){
			$_SESSION['partida'] == "";
			header("location:index.php");
		}
		
		try {
			$stmt = $this->conn->prepare("INSERT INTO partidas (idSala) VALUES (:s)");
			$stmt->execute(["s" => $this->sala]);
			
			$_SESSION['partida'] = $this->conn->lastInsertId();
			$this->partida = $this->conn->lastInsertId();
			header("location:index.php");
		} catch (PDOException $e) {
			echo "Erro" . $e->getMessage();
		}
	}
	
	//-> Adiciona ganhador
	public function addGanhador($g){
		try {
			$stmt = $this->conn->prepare("UPDATE partidas SET ganhador = :g WHERE id = :id");
			$stmt->execute(["g" => $g, "id" => $this->partida]);
		} catch (PDOException $e) {
			echo "Erro" . $e->getMessage();
		}
	}
	
	//-> Inicia Jogo
	public function iniciarJogo(){
		if($this->usuario == null){
			die();
		}
		
		echo "<link rel='stylesheet' href='style.css'>";

		echo "Jogador: {$this->usuario} | <a href='?logout'>Logout</a><br>";
		echo "ID do Jogador: {$this->usuarioUUID}<br>";
		echo "Sou o {$this->jogador}<br>";
		echo "Sala: {$this->sala}<br>";
		echo "Partida: {$this->partida}<hr>";
		
		//-> Verifica se esta em uma sala e se dados são validos
		if($this->sala == null){
			try {
				$stmt = $this->conn->prepare("SELECT * FROM sala WHERE idJogador1 = :u OR idJogador2 = :u");
				$stmt->execute(["u" => $this->usuarioUUID]);
				echo "<b>Salas criadas</b>";
				while($lista = $stmt->fetch()){
					echo "<br><a href='?sala={$lista['uuid']}'>";
					echo "Sala: {$lista['uuid']} | Entrar </a>";
					echo "<br>Convite: localhost:8080/jokento/index.php?convite={$lista['uuid']}";
				}
			} catch (PDOException $e) {
				echo "Erro" . $e->getMessage();
			}

		}else{
			echo "Sala: $this->sala | <a href='?sairsala'>Sair da Sala</a>";
			echo "<hr>";
			try {
				$stmt = $this->conn->prepare("SELECT * FROM partidas WHERE id = :id");
				$stmt->execute(["id" => $this->partida]);
				 
				$lista = $stmt->fetch();
				
				if($lista){
					$j1Escolha = $lista['j1Escolha'];
					$j2Escolha = $lista['j2Escolha'];
				}else{
					die("Partida finalizada");
				}
			}catch(PDOException $e){
				echo "Error" . $e->getMessage();
			}
			
			$menu = true;   
			if($j1Escolha === NULL){
				echo "<br>Status: Aguardando Jogador 1";
				if($this->jogador == "Jogador2"){$menu = false;}
				echo "<script>setTimeout(function(){location.reload()},4000);</script>";
			}elseif($j2Escolha === NULL){
				echo "<br>Status: Aguardando Jogador 2";
				if($this->jogador == "Jogador1"){$menu = false;}
				echo "<script>setTimeout(function(){location.reload()},4000);</script>";
			}else{
				echo "<br>".$this->validaRodada($j1Escolha , $j2Escolha);
				if($this->jogador == "Jogador1"){
					echo "<br><a href='?novarodada={$this->sala}'>Nova rodada</a>";
				}else{
					echo "<br><a href='?novarodada=0'>Nova rodada</a>";
				}			
				
				$menu = false;
			}
			
			
			if($menu){
				echo "<hr>";
				echo "<div class='collection'>";
				echo "<div class='menu__container'><div class='menu'><a href='?jogada=0'>Pedra</a></div></div> <br>";
				echo "<div class='menu__container'><div class='menu'><a href='?jogada=1'>Papel</a></div></div> <br>";
				echo "<div class='menu__container'><div class='menu'><a href='?jogada=2'>Tesoura</a></div></div> <br>";
				echo "<div class='menu__container'><div class='menu'><a href='?jogada=3'>Spock</a></div></div> <br>";
				echo "<div class='menu__container'><div class='menu'><a href='?jogada=4'>Lagarto</a></div></div> <br>";
				echo "</div>";
			}
		}
	}
}
?>