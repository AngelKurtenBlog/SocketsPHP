<?php
	class Socket
	{
		private static $ws;
		private static $ws_new;
		private static $clients;
		private static $message;
		/**
		 * Create Socket
		 * @param string $domain
		 * @param string $type 
		 * @param string $protocol 
		 * @return socket
		 */
		static function Create($domain, $type, $protocol)
		{
			try {
				self::$ws = socket_create($domain, $type, $protocol);
				socket_set_option(self::$ws, SOL_SOCKET, SO_REUSEADDR, 1);

				if(self::$ws == FALSE)
				{
					throw new Exception("Error al crear el socket", 1);
				}
				else
				{
					return self::$ws;
				}
			} catch (Exception $e) {
				
				echo $e->getMessage();

			}

		}

		/**
		 * link address with the socket
		 * @param string $addres 
		 * @param int $port
		 * @return boolean
		 */
		static function Bind($addres, $port)
		{
			try {

				socket_bind(self::$ws, 0, $port);
				if(self::$ws == FALSE)
				{
					throw new Exception("Error al vincular el socket con el address", 1);
				}
				else
				{
					return self::$ws;
				}
				
			} catch (Exception $e) {
				echo $e->getMessage();
			}
		}

		/**
		 * Listen connections
		 * @return socket
		 */
		static function Listen()
		{
			return socket_listen(self::$ws);
		}

		/**
		 * Accept connections
		 * @return socket
		 */
		static function Accept()
		{
			self::$ws_new = socket_accept(self::$ws);

			return self::$ws_new;
		}

		/**
		 * Clients Conneted
		 * @return type
		 */
		static function Clients()
		{
			self::$clients = array(self::$ws);
			return self::$clients;
		}

		/**
		 * Validate connection Client - Server
		 * @param socket $header 
		 * @param string $host 
		 * @param int $port 
		 */
		static function ValidationClient($cliente, $header, $host, $port)
		{
			$headers = array();
			$lines = preg_split("/\r\n/", $header);
			foreach ($lines as $line) {
				$line = chop($line);
				if(preg_match('/\A(\S+): (.*)\z/', $line, $matches))
				{
					$headers[$matches[1]] = $matches[2];
				}
			}

			$secKey = $headers['Sec-WebSocket-Key'];

			$secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
			$upgrade  = "HTTP/1.1 101 Web Socket\r\n" .
			"Upgrade: websocket\r\n" .
			"Connection: Upgrade\r\n" .
			"WebSocket-Origin: $host\r\n" .
			"WebSocket-Location: ws://$host:$port/demo/shout.php\r\n".
			"Sec-WebSocket-Accept:$secAccept\r\n\r\n";
			socket_write($cliente,$upgrade,strlen($upgrade));
		}

		/**
		 * Get ip client
		 * @return string
		 */
		static function GetClient($socket)
		{
			socket_getpeername($socket, $ip);

			return $ip;
		}

		/**
		 * Send Message Client
		 * @param string $msg 
		 * @param socket $socket 
		 */
		static function SendMessage($msg, $socket)
		{
			@socket_write($socket, $msg, strlen($msg));
		}

		/**
		 * Recived message client
		 * @param type $socket 
		 * @return int
		 */
		static function RecvMessage($socket)
		{
			$bin = socket_recv($socket, $buf, 1024, 0);
			@self::$message = $buf;			
			return $bin;
		}

		/**
		 * Return message recived
		 * @return string
		 */
		static function GetMessage()
		{
			return self::$message;
		}
		
		/**
		 * Enctript message
		 * @param string $msg 
		 * @return string
		 */
		static function EncriptMessage($msg)
		{
			$b1 = 0x80 | (0x1 & 0x0f);
			$lenght = strlen($msg);

			if($lenght<=125)
				$header = pack('CC', $b1, $lenght);
			elseif($lenght>125 && $lenght < 65536)
				$header = pack('CCn', $b1,126, $lenght);
			elseif($lenght>=65536)
				$header = pack('CCNN', $b1, 127, $lenght);

			return $header.$msg;
		}

		/**
		 * Decript message
		 * @param string $msg 
		 * @return string
		 */
		static function DecriptMessage($msg)
		{
			$lenght = ord($msg[1]) & 127;
			if($lenght == 126) {
				$masks = substr($msg, 4, 4);
				$data = substr($msg, 8);
			}
			elseif($lenght == 127) {
				$masks = substr($msg, 10, 4);
				$data = substr($msg, 14);
			}
			else {
				$masks = substr($msg, 2, 4);
				$data = substr($msg, 6);
			}
			$msg = "";
			for ($i = 0; $i < strlen($data); ++$i) {
				$msg .= $data[$i] ^ $masks[$i%4];
			}
			
			return $msg;
		}

		/**
		 * Return socket
		 * @return socket
		 */
		static function GetSocket()
		{
			return self::$ws;
		}

		/**
		 * Read info socket
		 * @param socket $socket 
		 * @return socket
		 */
		static function ReadSocket($socket, $normal=FALSE)
		{
			if(!$normal){
				return @socket_read($socket, 1024);
			}
			else
			{
				return @socket_read($socket, 1024, PHP_NORMAL_READ);
			}
			
		}

		/**
		 * Close socket
		 */
		static function CloseSocket()
		{
			socket_close(self::$ws);
			socket_close(self::$ws_new);
		}
	}
?>