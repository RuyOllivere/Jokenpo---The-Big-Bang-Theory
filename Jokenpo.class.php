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
			// echo "<link rel='stylesheet' href='style.css'>";
			// echo '<form method="post">';
			// echo '<input type="text" name="user" placeholder="Usuário"><br>';
			// echo '<input type="password" name="pass" placeholder="Senha"><br>';
			// echo '<input type="submit" value="Entrar">';
			// echo '</form>';

			    echo '
    			<!DOCTYPE html>
    			<html lang="en">
    			<head>
    			  <meta charset="UTF-8">
    			  <meta name="viewport" content="width=device-width, initial-scale=1.0">
    			  <title>Glassmorphism Login</title>
    			  <link rel="stylesheet" href="login.css">
    			</head>
    			<body>
    			  <div class="container">
    			    <div class="login-card">
					
    			      <div class="trail trail-1"></div>
    			      <div class="trail trail-2"></div>
					
    			      <div class="avatar-container">
    			        <div class="avatar-icon">
    			          <svg width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
    			            <path d="M50 45H10C8.89543 45 8 44.1046 8 43V22C8 20.8954 8.89543 20 10 20H16L19 15H41L44 20H50C51.1046 20 52 20.8954 52 22V43C52 44.1046 51.1046 45 50 45Z" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
    			            <circle cx="30" cy="32" r="8" stroke="currentColor" stroke-width="2.5"/>
    			          </svg>
    			        </div>
    			      </div>
					
    			      <form method="post">
					
    			        <div class="input-group">
    			          <div class="input-icon">
    			            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
    			              <path d="M10 10C12.7614 10 15 7.76142 15 5C15 2.23858 12.7614 0 10 0C7.23858 0 5 2.23858 5 5C5 7.76142 7.23858 10 10 10Z" fill="currentColor"/>
    			              <path d="M10 12.5C5 12.5 2.5 15 2.5 17.5V20H17.5V17.5C17.5 15 15 12.5 10 12.5Z" fill="currentColor"/>
    			            </svg>
    			          </div>
    			          <input type="text" name="user" placeholder="Username" id="username">
    			        </div>
					
    			        <div class="input-group">
    			          <div class="input-icon">
    			            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
    			              <rect x="3" y="9" width="14" height="10" rx="2" stroke="currentColor" stroke-width="2" fill="none"/>
    			              <path d="M6 9V6C6 3.79086 7.79086 2 10 2C12.2091 2 14 3.79086 14 6V9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    			              <circle cx="10" cy="14" r="1.5" fill="currentColor"/>
    			            </svg>
    			          </div>
    			          <input type="password" name="pass" placeholder="Password" id="password">
    			        </div>
					
    			        <button class="login-btn" type="submit">LOGIN</button>
					
    			      </form>
					
    			    </div>
    			  </div>
					
    			  <script src="scripts/script.js"></script>
    			</body>
    			</html>
    			';

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


	public function getUsuario(){return $this->usuario;}
	public function getUsuarioUUID(){return $this->usuarioUUID;}
	public function getJogador(){return $this->jogador;}
	public function getSala(){return $this->sala;}
	public function getPartida(){return $this->partida;}

	public function listarSalas(){

	}

	
	//-> Inicia Jogo
	public function iniciarJogo(){
		if($this->usuario == null){
			die();
		}
		
		echo "<link rel='stylesheet' href='style.css'>";

		echo "
		<div class='info-box'>
		    Jogador: {$this->usuario} | <a href='?logout'>Logout</a><br>
		    ID do Jogador: {$this->usuarioUUID}<br>
		    Sou o {$this->jogador}<br>
		    Sala: {$this->sala}<br>
		    Partida: {$this->partida}<hr>
		</div>";
		
		//-> Verifica se esta em uma sala e se dados são validos
		if($this->sala == null){
			try {
				$stmt = $this->conn->prepare("SELECT * FROM sala WHERE idJogador1 = :u OR idJogador2 = :u");
				$stmt->execute(["u" => $this->usuarioUUID]);
				echo "<div class='salas-box'>";
				echo "<div class='salas-title'><b>Salas criadas</b></div>";

				while($lista = $stmt->fetch()){
				
				    echo "<div class='sala-item'>";
				    echo "<a href='?sala={$lista['uuid']}'>Sala: {$lista['uuid']} — Entrar</a>";
				    echo "<span class='convite-link'>Convite: localhost:8080/jokento/index.php?convite={$lista['uuid']}</span>";
				    echo "</div>";
				}

				echo "</div>";

			} catch (PDOException $e) {
				echo "Erro" . $e->getMessage();
			}

		}else{
			echo "<div class='sala-atual-box'>";
			echo "Sala: {$this->sala} | <a href='?sairsala'>Sair da Sala</a>";
			echo "</div>";

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
			
			echo "<div class='status-box'>";

			$menu = true;   

			if($j1Escolha === NULL){
			
			    echo "Status: Aguardando Jogador 1";
			    if($this->jogador == 'Jogador2'){ $menu = false; }
			
			    echo "<script>setTimeout(function(){location.reload()},4000);</script>";
			
			}elseif($j2Escolha === NULL){
			
			    echo "Status: Aguardando Jogador 2";
			    if($this->jogador == 'Jogador1'){ $menu = false; }
			
			    echo "<script>setTimeout(function(){location.reload()},4000);</script>";
			
			}else{
			
			    echo $this->validaRodada($j1Escolha, $j2Escolha);
			
			    if($this->jogador == 'Jogador1'){
			        echo "<br><a href='?novarodada={$this->sala}'>Nova rodada</a>";
			    }else{
			        echo "<br><a href='?novarodada=0'>Nova rodada</a>";
			    }
			
			    $menu = false;
			}

			echo "</div>";

			
			
			if($menu){
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