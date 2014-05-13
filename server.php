<?php
	require_once ('socket.php');

	$host = 'angelkurten.com';
	$port = '8080';
	$null = NULL;
	$changed = array();
	$clients = array();

	$ws_new =array();

	Socket::Create(AF_INET, SOCK_STREAM, SOL_TCP);

	Socket::Bind($host, $port);

	Socket::Listen();

	$ws = Socket::GetSocket();

	$clients = Socket::Clients();

		while (true) {
		$changed = $clients;
		
		socket_select($changed, $null, $null, 0, 10);
		
		if(in_array($ws, $changed))
		{
			$ws_new = Socket::Accept();
			$clients[] = $ws_new;

			$header = Socket::ReadSocket($ws_new);
			Socket::ValidationClient($ws_new, $header, $host, $port);

			$response_new_client = Socket::EncriptMessage(json_encode(array('type'=>'system', 'message'=>Socket::GetClient($ws_new).' conectado')));

			foreach ($clients as $ws_client) {
				Socket::SendMessage($response_new_client, $ws_client);
			}

			//make room for new socket
			$found_socket = array_search($ws, $changed);
			unset($changed[$found_socket]);
		}

		//bucle for connected sockets
		foreach ($changed as $changed_ws) {
			while (Socket::RecvMessage($changed_ws) >= 1) {
				//obtener el mensaje
				$received_text = Socket::GetMessage();
				//desencriptar el mensaje
				$received_text = Socket::DecriptMessage($received_text);
				//leer json y decoficarlo
				$received_text = json_decode($received_text);
				//obtener los datos enviados
				$user_name = $received_text->name;
				$user_message = $received_text->message;
				$user_color = $received_text->color;
				//construir el mensaje a retransmitir y encriptar el mensaje
				$response_text = Socket::EncriptMessage(json_encode(array('type'=>'usermsg', 'name'=>$user_name, 'message'=>$user_message, 'color'=>$user_color)));
				//recorrer los usuarios conectados y enviar el mensaje
				foreach ($clients as $ws_client) {
					Socket::SendMessage($response_text, $ws_client);					
				}
				//regresar al ciclo infito / while
				break 2;
			}
			//trater de leer informacion del socket
			$buf = Socket::ReadSocket($changed_ws, TRUE);
			//verificar que este conectado el cliente
			if($buf === false)
			{
				//buscar socket
				$socket = array_search($changed_ws, $clients);
				//retornar la ip del socket
				$ip = Socket::GetClient($changed_ws);
				//eliminar socket de la lista de clientes
				unset($clients[$socket]);
				//construir, codificar a json y encriptar mensaje del sistema informando lo sucedido
				$response = Socket::EncriptMessage(json_encode(array('type'=>'system', 'message'=>$ip.' desconectado')));
				//enviar informacion a los clientes
				foreach ($clients as $ws_client) {
					Socket::SendMessage($response, $ws_client);					
				}
			}
		}
	}
?>