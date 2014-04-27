<?php

namespace WateringSystem\Model;

/**
 * Retrieve sensor readings
 * @author James Cooke
 *
 */
class SensorReadingModel extends WateringSystemModelAbstract 
{
	protected $port;
	/** time to wait for a response when reading from serial port*/
	protected $readWait = 2;
	
	public function __construct(array $parameters)
	{
		if (!isset($parameters['port']) || !is_string($parameters['port'])) {
			throw new \Exception('Sensor port must be set');
		}
		
		if (isset($parameters['readWait']) && is_numeric($parameters['readWait'])) {
			$this->readWait = (int) $parameters['readWait'];
		}
		
		$this->port = $parameters['port'];
	}
	
	/**
	 * Get sensor readings
	 * @throws \Exception
	 */
	public function getSensorReadings()
	{
		//get a pointer to the serial port
		$pointer = $this->getPointer();
		if ($pointer === false) {
			throw new \Exception('Could not open serial port');
		}
		//send the status message
		$this->sendMessage($pointer, 'status');
		//read the reply
		$response = $this->readMessage($pointer);
		//close the pointer
		$this->close($pointer);
		
		//decode the reponse and check for validity
		$json = json_decode($response);
		if (isset($json->result)) {
			if ($json->result) {
				return $json->result;
			} else {
				$error = (isset($response->message)) ? $response->message : 'Error in sensor reading';
				throw new \Exception($error);
			}
		} else {
			throw new \Exception('Could not get a sensor reading');
		}
	}
	
	/**
	 * Get a file pointer to the serial port
	 * @return filepointer|false false if cannot open
	 */
	protected function getPointer()
	{
		return @fopen($this->port, "w+");
	}
	
	/**
	 * Read from the serial port for a maximum of $this->readWait seconds 
	 * or until we receive a carriage return (\r\n)
	 * @param unknown $pointer
	 * @return string
	 */
	protected function readMessage($pointer)
	{
		$readLength = 128;
		$message = '';
		$startTime = time();

		while (time() < $startTime + $this->readWait) {
			$message .= @fread($pointer, $readLength);
			$lastChars = substr($message, strlen($message) - 2, 2);
			if ($lastChars == "\r\n") {
				break;
			}
			usleep(100);
		}
		
		return $message;
	}
	
	/**
	 * Close the pointer
	 * @param unknown $pointer
	 * @return boolean
	 */
	protected function close($pointer)
	{
		@fclose($pointer);
		return true;
	}
	
	/**
	 * Send a message to the serial port
	 * @param unknown $pointer
	 * @param String $message
	 * @return boolean
	 */
	function sendMessage($pointer, $message)
	{
		$result = @fwrite($pointer, $message);
		return ($result !== false);
	}
}