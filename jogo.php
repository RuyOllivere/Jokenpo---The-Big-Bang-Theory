<?php 
session_start();
include_once "conexao.php";

$opcoes = Array("Pedra" , "Papel" , "Tesoura", "Spock", "Lagarto");
$jogador = 0;
// 0 pedra, 1 papel, 2 tesoura, 3 Spock, 4 lagarto
function validaRodada($p1 , $p2){

    global $opcoes;

    foreach($opcoes as $i){  
        $mode = $opcoes;
        $mode_next = next($opcoes);
        $mode_next_next = next($opcoes);

        if($p1 == $i){
            if($p2 == $i){
                return "Empate";
            }elseif($p2 == $mode_next){
                return "Player 1 ganhou!";
            }elseif($p2 == $mode_next_next){
                return "Player 2 ganhou!";
            }
        }
    }   

    // if ($p1  == $p2 ){
    //     return "Empate";
    // }elseif($p1  == 0 && $p2  == 2){
    //     return "Player 1 ganhou!";
    // }elseif($p1 == 2 && $p2  == 0){
    //     return "Player 2 ganhou!";
    // }elseif($p1 == 3 && $p2  == 1){
    //     return "Player 1 ganhou!";
    // }elseif($p1 == 1 && $p2 == 3){
    //     return "Player 2 ganhou!";
    // }elseif($p1 == 3 && $p2  == 4){
    //     return "Player 1 ganhou!";
    // }elseif($p1 == 1 && $p2 == 4){
    //     return "Player 2 ganhou!";
    // }elseif($p1 == 4 && $p2 == 2){
    //     return "Player 1 ganhou!";
    // }elseif($p1 == 2 && $p2 == 4){
    //     return "Player 2 ganhou!";
    // }elseif($p1 == 2 && $p2 == 4){
    //     return "Player 2 ganhou!";
    // }elseif($p1 > $p2 ){
    //     return "Player 1 ganhou!";
    // }else{
    //     return "Player 2 ganhou!";
    // }

}

function uuidv4(){
  $data = random_bytes(16);

  $data[6] = chr(ord($data[6]) & 0x0f | 0x40); 
  $data[8] = chr(ord($data[8]) & 0x3f | 0x80);  
    
  return vsprintf('%s-%s-%s', str_split(bin2hex($data), 4));
}

//---------------------------------------
 
//-> Login 
if(isset($_GET['login'])){
   $_SESSION['usuario'] = $_GET['login']; 
   header("location: jogo.php");
}

if(!isset($_SESSION['usuario'])){
	echo '<form>';
	echo '<input type="text" name="login">';
	echo '<input type="submit" value="entrar">';
	echo '</form>';
	die();
}
$usuario = $_SESSION['usuario'];

echo "Usuário: $usuario<hr>";

//-> Verifica se foi selecionada uma sala
$sala = null;
if(isset($_SESSION['sala'])){
    if(($_SESSION['sala'] != "")){
        $sala = $_SESSION['sala'];
    }
}

//-> Seleciona sala
if(isset($_GET['sala'])){
 try {
        $stmt = $pdo->prepare("SELECT * FROM sala WHERE idJogador1 = :u AND uuid = :id");
        $stmt->execute(["u" => $usuario, "id" => $_GET['sala']]);
        $dados = $stmt->fetchAll();
        if(count($dados)){
            $_SESSION['sala'] = $_GET['sala'];
            header("location: jogo.php");
        }else{
            echo "Não tem permissão";
        }

    } catch (PDOException $e) {
        echo "Erro" . $e->getMessage();
    }
}

//-> Verifica convite
if(isset($_GET['convite'])){
 try {
        $stmt = $pdo->prepare("SELECT * FROM sala WHERE idJogador2 = 0 AND uuid = :id");
        $stmt->execute([ "id" => $_GET['convite']]);
        $dados = $stmt->fetchAll();
        if(count($dados)){
            $stmt = $pdo->prepare("UPDATE sala SET idJogador2 = :id WHERE uuid = :uuid");
            $stmt->execute([ "id" => $usuario ,  "uuid" => $_GET['convite']]);
            $_SESSION['sala'] = $_GET['convite'];
            header("location: jogo.php");
        }else{
            echo "Não tem permissão";
        }

    } catch (PDOException $e) {
        echo "Erro" . $e->getMessage();
    }
}

//-> Sai da sala
if(isset($_GET['sair'])){
    $_SESSION['sala'] = null;
    header('location: jogo.php');
}



if(isset($_GET['novarodada'])){
	try {
        $stmt = $pdo->prepare("UPDATE partidas SET j1Escolha = NULL, j2Escolha = NULL WHERE idSala = :id");
        $stmt->execute(["id" => $_GET['novarodada']]);
	} catch (PDOException $e) {
        echo "Erro" . $e->getMessage();
    }	
}

//-> Verifica se esta em uma sala e se dados são validos
if($sala == null){
    try {
        $stmt = $pdo->prepare("SELECT * FROM sala WHERE idJogador1 = :u");
        $stmt->execute(["u" => $usuario]);
        echo "<b>Salas criadas</b>";
        while($lista = $stmt->fetch()){
            echo "<br><a href='?sala={$lista['uuid']}'>";
            echo "Sala: {$lista['uuid']} | Entrar </a>";
            echo "<br>Convite: localhost:8080/jokento/jogo.php?convite={$lista['uuid']}";
        }
    } catch (PDOException $e) {
        echo "Erro" . $e->getMessage();
    }

}else{
    echo "Sala: $sala | <a href='?sair'>Sair</a>";
    echo "<hr>";
    try {
        $stmt = $pdo->prepare('SELECT * FROM sala WHERE uuid = :uuid');
        $stmt->execute(["uuid" => $sala]);
        $dados = $stmt->fetch();
        if($dados['idJogador1'] == $usuario){
            $jogador = "Jogador1";
        }else if($dados['idJogador2'] == $usuario){
            $jogador = "Jogador2";
        }else{
            $_SESSION['sala'] = "";
            header("location: jogo.php");
        }
        $stmt = $pdo->prepare("SELECT * FROM partidas WHERE idSala = :s");
        $stmt->execute(["s" => $sala]);
         
        $lista = $stmt->fetch();
        $j1Escolha = $lista['j1Escolha'];
        $j2Escolha = $lista['j2Escolha'];
        
        
    }catch(PDOException $e){
        echo "Error" . $e->getMessage();
    }
    echo "Eu sou: $jogador";
    $menu = true;   

    if($j1Escolha === null){
        echo "<br>Status: Aguardando Jogador 1";
        if($jogador == "Jogador2"){$menu = false;}
		echo "<script>setTimeout(function(){location.reload()},4000);</script>";
    }elseif($j2Escolha === null){
        echo "<br>Status: Aguardando Jogador 2";
        if($jogador == "Jogador1"){$menu = false;}
        echo "<script>setTimeout(function(){location.reload()},4000);</script>";
    }else{
        echo "<br>".validaRodada($j1Escolha , $j2Escolha);
		echo "<br><a href='?novarodada=$sala'>Nova rodada</a>";
		
        $menu = false;
    }
	
	
    if($menu){
        echo "<hr>";
        echo "<a href='?jogada=0'>Pedra</a> <br>";
        echo "<a href='?jogada=1'>Papel</a><br>";
        echo "<a href='?jogada=2'>Tesoura</a><br>";

    }
}
?>