<?php
namespace Komercy;

/**
 * Komerci
 *
 * API de pagamento REDECARD.
 *
 * @package Kormercy
 * @subpackage Libraries
 * @category Libraries
 * @author André Gonçalves <andreseko@gmail.com>
 *
 */

Class Komerci {
	/**
	 * Version.
	 */
	const VERSION = '1.0.0';

	/**
	 * Recebe o valor Total da compra no formato ##.## com duas casas decimais
	 * @var float(10)
	 */
	private $Total;

	/**
	 * Recebe o código da transação conforme tabela abaixo:
	 * A vista -> 04 | Parcelado Emissor -> 06 | Parcelado Estabelecimento -> 08 |
	 * Pré-autorização -> 73 | IATA a vista -> 39 | IATA parcelado -> 40
	 *
	 * @var string(2)
	 */
	private $Transacao;

	/**
	 * Recebe o número Total de Parcelas
	 *
	 * @var string(2)
	 */
	private $Parcelas;

	/**
	 * Número de filiação do estabelecimento fornecedor
	 * @var string(9)
	 */
	private $Filiacao;

	/**
	 * Número de filiação do estabelecimento distribuidor / portador do cartão no caso de B2B
	 * @var string(9)
	 */
	private $Distribuidor = NULL;

	/**
	 * Número do pedido gerado pelo estabelecimento
	 * @var string(16)
	 */
	private $NumPedido;

	/**
	 * Número do cartão de crédito (VISA, MASTERCARD e DINERS).
	 * @var string(16)
	 */
	private $Nrcartao;
	/**
	 * Codigo verificador do cartão
	 * @var int(3)
	 */
	private $CVC2;

	/**
	 * Mês da validade do cartão
	 * @var string(2)
	 */
	private $Mes;

	/**
	 * Ano da validade do cartão
	 * @var string(2)
	 */
	private $Ano;

	/**
	 * Nome do Portador do cartão
	 * @var string(50)
	 */
	private $Portador;

	/**
	 * Parâmetro Opcional para Companhia Aérea. N/A - Enviar parâmetro com valor vazio
	 * @var string
	 */
	private $IATA = null;

	/**
	 * N/A - Enviar parâmetro com valor vazio
	 * @var string(5)
	 */
	private $Concentrador = null;

	/**
	 * Taxa de Embarque. Opcional. Só utilizado para Companhia Aérea.
	 * @var float(10)
	 */
	private $TaxaEmbarque = null;

	/**
	 * N/A - Enviar parâmetro com valor vazio
	 * @var string(10)
	 */
	private $Entrada = null;

	/**
	 * Texto Livre - Enviar parâmetro com valor vazio
	 * @var string(26)
	 */
	private $Pax1 = null;

	/**
	 * Texto Livre - Enviar parâmetro com valor vazio
	 * @var string(26)
	 */
	private $Pax2 = null;

	/**
	 * Texto Livre - Enviar parâmetro com valor vazio
	 * @var string(26)
	 */
	private $Pax3 = null;

	/**
	 * Texto Livre - Enviar parâmetro com valor vazio
	 * @var string(26)
	 */
	private $Pax4 = null;

	/**
	 * Texto Livre - Enviar parâmetro com valor vazio
	 * @var string(16)
	 */
	private $Numdoc1 = null;

	/**
	 * Texto Livre - Enviar parâmetro com valor vazio
	 * @var string(16)
	 */
	private $Numdoc2 = null;

	/**
	 * Texto Livre - Enviar parâmetro com valor vazio
	 * @var string(16)
	 */
	private $Numdoc3 = null;

	/**
	 * Texto Livre - Enviar parâmetro com valor vazio
	 * @var string(16)
	 */
	private $Numdoc4 = null;

	/**
	 * “Flag” de confirmação
	 * Ao enviar este parâmetro preenchido com o valor “S”, o método do webservice “ConfirmTxn” será acionada automaticamente.
	 * Utilize esta opção com cautela.
	 * Não há a garantia de acionamento da operação.
	 * Ao utilizar esta opção, obrigatoriamente deverão ser tratados os parâmetros CONFCODRET e CONFMSGRET devolvidos na etapa 2.
	 * Se o valor do campo CONFCODRET for diferente de 0 será o necessário acionar a operação “ConfirmTxn” para confirmar a transação
	 *
	 * @var char(1)
	 */
	private $ConfTxn = 'S';

	/**
	 * N/A – Enviar parâmetro com valor vazio
	 * @var string
	 */
	private $Add_Data = null;

	protected $wsdl_uri;

	private $aceitaParcelamento = TRUE;
	private $numeroParcelas = 0;
	private $numMaxParcelas = 12;
	private $parcelamentoEstabelecimento = TRUE;

	public function __construct($numeroFiliacao, $numeroDistribuidor = '') {
		ini_set("soap.wsdl_cache_enabled", 0);

		$this->setFiliacao($numeroFiliacao);
		$this->Distribuidor = $numeroDistribuidor;

		$this->wsdl_uri = 'https://ecommerce.redecard.com.br/pos_virtual/wskomerci/cap.asmx?WSDL';

		self::configure();
	}

	/**
	 * @method configure
	 *
	 * Inicializa a aplicação do Komerci
	 *
	 * A configuração:
	 * Seta algumas propriedades conforme arquivo de configuração.
	 * - Seta o número de filiação
	 * - Seta o número de Distribuidor (opcional)
	 * - Se o sistema não aceitar parcelamento, já seta o número de Parcelas para 00.
	 */
	public function configure($aceitaParcelamento = TRUE, $numeroParcelas = 0, $numMaxParcelas = 12, $parcelamentoEstabelecimento = TRUE) {
	    $this->aceitaParcelamento = $aceitaParcelamento;
	    $this->numeroParcelas = $numeroParcelas;
	    $this->numMaxParcelas = $numMaxParcelas;
	    $this->parcelamentoEstabelecimento = $parcelamentoEstabelecimento;

      $this->setNumParcelas();
	}

	/**
	 * @method getError
	 * @access private
	 * @param int(2) $cod Recebe o código do erro
	 * @return string message
	 */
	private static function getError($cod) {
		$errorList = array(
				0 => 'Compra realizada com sucesso.'
				,1 => 'A transação já foi confirmada anteriormente.'
				,2 => 'A transação de confirmação foi negada pelo autorizador.'
				,3 => 'A transação foi desfeita, pois o tempo disponível de dois minutos para confirmação foi ultrapassado.'
				,4 => 'A transação foi estornada anteriormente pelo processo de estorno de transação.'
				,5 => 'A transação foi estornada anteriormente pelo processo de estorno de transação.'
				,8 => 'Dados de Total e Número de Pedido não conferem com o Número de Comprovante e Autorização passados como parâmetro.'
				,9 => 'Não foi encontrada nenhuma transação para os respectivos campos passados como parâmetro: NUMCV, NUMAUTOR e DATA.'
				,20 => 'Parâmetro obrigatório ausente'
				,21 => 'Número de filiação em formato inválido'
				,22 => 'Número de parcelas incompatível com a transação'
				,23 => 'Problemas no cadastro do estabelecimento.'
				,24 => 'Problemas no cadastro do estabelecimento.'
				,25 => 'Formatação incorreta da transação.'
				,26 => 'Formatação incorreta da transação.'
				,27 => 'Cartão inválido.'
				,28 => 'CVC2 em formato inválido.'
				,29 => 'Operação não permitida. Número do pedido de referência da transação IATA maior que 13 posições'
				,30 => 'Parâmetro AVS ausente.'
				,31 => 'Número do pedido maior que o permitido (16 posições).'
				,32 => 'Código IATA inválido ou inexistente.'
				,33 => 'Código IATA inválido.'
				,34 => 'Distribuidor inválido ou inexistente.'
				,35 => 'Problemas no cadastro do estabelecimento.'
				,36 => 'Operação não permitida.'
				,37 => 'Distribuidor inválido ou inexistente.'
				,38 => 'Operação não permitida no ambiente de teste.'
				,39 => 'Operação não permitida para o código IATA informado.'
				,40 => 'Código IATA inválido ou inexistente.'
				,41 => 'Problemas no cadastro do estabelecimento.'
				,42 => 'Problemas no cadastro do usuário do estabelecimento.'
				,43 => 'Problemas na autenticação do usuário.'
				,44 => 'Usuário incorreto para testes.'
				,45 => 'Problemas no cadastro do estabelecimento para testes.'
		    ,51 => 'Não autorizado. Verifique com a operadora de cartão de crédito.'
				,53 => 'Transação não autorizada, verifique os dados e tente novamente. Caso o erro persista, entre em contato com a operadora do cartão.'
				,56 => 'Transação não autorizada, verifique os dados e tente novamente. Caso o erro persista, entre em contato com a operadora do cartão.'
				,58 => 'Problemas com o cartão. Por favor, verifique os dados de seu cartão. Caso o erro persista, entre em contato com a central de atendimento de seu cartão.'
				,60 => 'Erro ao processar sua compra. Se está utilizando um cartão de crédito que também possui a função débito, utilize a opção PAGSEGURO para efetuar o pagamento.'
				,63 => 'Problemas com o cartão. Por favor, verifique os dados de seu cartão. Caso o erro persista, entre em contato com a central de atendimento de seu cartão.'
				,65 => 'Problemas com o cartão. Por favor, verifique os dados de seu cartão. Caso o erro persista, entre em contato com a central de atendimento de seu cartão.'
				,69 => 'Problemas com o cartão. Por favor, verifique os dados de seu cartão. Caso o erro persista, entre em contato com a central de atendimento de seu cartão.'
				,72 => 'Problemas com o cartão. Por favor, verifique os dados de seu cartão. Caso o erro persista, entre em contato com a central de atendimento de seu cartão.'
				,76 => 'Sua transação não pode ser concluída. Por favor, tente novamente.'
				,77 => 'Problemas com o cartão. Por favor, verifique os dados de seu cartão. Caso o erro persista, entre em contato com a central de atendimento de seu cartão.'
				,81 => 'Banco não pertence à rede. Utilize outro cartão de crédito.'
				,86 => 'Sua transação não pode ser concluída. Por favor, tente novamente.'
				,88 => 'Problemas com o cartão. Por favor, verifique os dados de seu cartão. Caso o erro persista, entre em contato com a central de atendimento de seu cartão.'
				,92 => 'Não autorizado'
		    ,96 => 'Problemas com o cartão. Por favor, verifique os dados de seu cartão. Caso o erro persista, entre em contato com a central de atendimento de seu cartão.'
		    ,98 => 'Não autorizado'
		    ,1001 => 'Data de validade obrigatória ser informada.'
		    ,1122 => 'Código de segurança do cartão inválido. Verifique se você digitou os 3 últimos dígitos no verso do cartão.'
			);

		if(array_key_exists($cod, $errorList)) {
		    return $errorList[$cod];
		}

		return false;
	}

	private function getParamters() {
	    $parameters               = new \stdClass();
	    $parameters->Total        = $this->Total;
	    $parameters->Transacao    = $this->Transacao;
	    $parameters->Parcelas     = $this->Parcelas;
	    $parameters->Filiacao     = $this->Filiacao;
	    $parameters->NumPedido    = $this->NumPedido;
	    $parameters->Nrcartao     = $this->Nrcartao;
	    $parameters->CVC2         = $this->CVC2;
	    $parameters->Mes          = $this->Mes;
	    $parameters->Ano          = $this->Ano;
	    $parameters->Portador     = $this->Portador;
	    $parameters->IATA         = $this->IATA;
	    $parameters->Distribuidor = $this->Distribuidor;
	    $parameters->Concentrador = $this->Concentrador;
	    $parameters->TaxaEmbarque = $this->TaxaEmbarque;
	    $parameters->Entrada      = $this->Entrada;
	    $parameters->Pax1         = $this->Pax1;
	    $parameters->Pax2         = $this->Pax2;
	    $parameters->Pax3         = $this->Pax3;
	    $parameters->Pax4         = $this->Pax4;
	    $parameters->Numdoc1      = $this->Numdoc1;
	    $parameters->Numdoc2      = $this->Numdoc2;
	    $parameters->Numdoc3      = $this->Numdoc3;
	    $parameters->Numdoc4      = $this->Numdoc4;
	    $parameters->ConfTxn      = $this->ConfTxn;
	    $parameters->Add_Data     = $this->Add_Data;

	    return $parameters;
	}

	public function dumpParamters() {
	    var_dump(self::getParamters());
	}

	/**
	 * @method requestPayment
	 *
	 * Método que inicia a transação com o Komerci
	 *
	 * @return object
	 */
	public function requestPayment() {

		$return = false;

		try {
			$oSoap = new \SoapClient($this->wsdl_uri);
			$oResponse = $oSoap->GetAuthorized(self::getParamters());
			$oAuthorizedResponse = $oResponse->GetAuthorizedResult;
			$xmlResponse = simplexml_load_string($oAuthorizedResponse->any);

			$codeError = htmlentities(urldecode((string)$xmlResponse->CODRET));
			$msgRedecard = utf8_decode(urldecode((string)$xmlResponse->MSGRET));

			if ($codeError == '0') {
				$data     = htmlentities(urldecode((string)$xmlResponse->DATA));
				$numAut   = htmlentities(urldecode((string)$xmlResponse->NUMAUTOR));
				$numCv    = htmlentities(urldecode((string)$xmlResponse->NUMCV));

				$URLCupom = "https://ecommerce.redecard.com.br/pos_virtual/cupom.asp?"
				."DATA=$data&"
				."Transacao=201&"
				."NUMAUTOR=$numAut&"
				."NUMCV=$numCv&"
				."Filiacao=$this->Filiacao";

				$msgRetorno = self::getError($codeError);
				if(!$msgRetorno) {
				    $msgRetorno = $msgRedecard;
				}

				$return = array(
						'hasError' => FALSE
						, 'message' => $msgRetorno
						, 'cupom' => $URLCupom
						, 'numAutor' => $numAut
						, 'numCV' => $numCv
					);
			} else {
		    $msgRetorno = self::getError($codeError);
		    if(!$msgRetorno) {
		        $msgRetorno = $msgRedecard;
		    }

				$return = array('hasError' => TRUE, 'code' => $codeError, 'log' => $msgRetorno, 'message' => 'Código: '. $codeError . ' - ' . $msgRetorno);
			}

		} catch (\SoapFault $fault) {
		  throw new \Exception("Erro de comunicação com webservice REDECARD. " . $fault->getMessage(), $fault->getCode());
		}

		return $return;
	}

	/**
	 * @method debug
	 *
	 * Método para debug
	 *
	 * @access public
	 * @return string
	 */
	public function debug() {
		echo '<pre>';
		var_dump($this);
		echo '</pre>';
	}

	/**
	 * @method setTotal
	 *
	 * @access public
	 * @param float(15) $Total seta o Total da compra realizada
	 */
	public function setTotal($Total) {
		$this->Total = trim(substr(str_replace(',', '.', $Total), 0, 15));
	}

	/**
	 * @method setTransacao
	 *
	 * O parâmetro deverá conter o código do tipo de transação a ser processada,
	 * de acordo com a tabela a seguir:
	 * A vista -> 04 |
	 * Parcelado Emissor -> 06 |
	 * Parcelado Estabelecimento -> 08 |
	 * Pré-autorização -> 73 |
	 * IATA a vista -> 39 |
	 * IATA parcelado -> 40
	 *
	 * @access public
	 * @param int(2) $Transacao
	 */
	public function setTransacao($Transacao) {
		$this->Transacao = str_pad(substr($Transacao, 0, 2), 2, '0', STR_PAD_LEFT);
	}

	/**
	 * @method setNumParcelas
	 *
	 * @access public
	 * @param int(2) $Parcelas deverá conter o nº de Parcelas da transação. Ele deverá ser preenchido com o valor “00” (zero zero) quando o parâmetro “Transacao” for “04” ou “39”, será à vista.
	 */
	public function setNumParcelas($Parcelas = 0) {
		if($this->aceitaParcelamento && $Parcelas > 0) {
			if(intval($Parcelas) <= $this->numMaxParcelas) {
				$this->Parcelas = str_pad(intval($Parcelas), 2, '0', STR_PAD_LEFT);
			} else {
				$this->Parcelas = $this->numMaxParcelas;
			}

			if($this->parcelamentoEstabelecimento) {
				$this->setTransacao(8);
			} else {
				$this->setTransacao(6);
			}
		} else {
			$this->Parcelas = '00';
			$this->setTransacao(4);
		}
	}

	/**
	 * @method setFiliacao
	 *
	 * @access private
	 * @param int(9) $filiado O parâmetro deverá conter o nº de filiação do estabelecimento cadastrado com a Redecard.
	 */
	private function setFiliacao($filiado) {
		$this->Filiacao = str_pad($filiado, 9, '0', STR_PAD_LEFT);
	}

	/**
	 * @method setNumPedido
	 *
	 * @access public
	 * @param long(16) $pedido O parâmetro deverá conter o nº do pedido referente ao produto / serviço solicitado
	 * pelo usuário. Este campo deverá ser preenchido de acordo com a política interna da loja-virtual.
	 * O sistema da Redecard não valida esse parâmetro.
	 */
	public function setNumPedido($pedido) {
		$this->NumPedido = trim(substr($pedido, 0 ,16));
	}

	/**
	 * @method setPortador
	 * @access public
	 * @param string(50) $portador O parâmetro deverá conter o nome do portador da forma que foi informado por ele.
	 * Este parâmetro não é validado pelo emissor do cartão.
	 */
	public function setPortador($portador) {
		$this->Portador = $portador;
	}

	/**
	 * @method setNrcartao
	 * @access public
	 * @param long(16) $card O parâmetro deverá conter o número do cartão de crédito do portador, podendo ser MasterCard, Diners ou Visa. Não são aceitos cartões de Débito.
	 */
	public function setNrcartao($card) {
	  $c = preg_replace("/[^0-9]/", "", $card);;
		$this->Nrcartao = $c;
	}

	/**
	 * @method setCodVerificador
	 *
	 * Os dados do portador do cartão (cartão validade, CVC2, etc) não devem ser armazenados pelo Estabelecimento.
	 * Apenas devem ser trafegados no momento do pedido do código de autorização da transação.
	 * Venda Recorrente é toda transação à vista com periodicidade constante.
	 * Exemplo: Assinatura de Revistas e Jornais
	 *
	 * @access public
	 * @param int(3) $cvc Este parâmetro é obrigatório com exceção dos estabelecimentos que trabalham no seguimento de venda recorrente, pois devem enviar este campo como vazio.
	 */
	public function setCodVerificador($cvc) {
		$this->CVC2 = $cvc;
	}

	/**
	 * @method setMes
	 * @access public
	 * @param int(2) $mes O parâmetro deverá conter o mês de validade do cartão do portador com duas posições (FORMATO MM).
	 */
	public function setMes($mes) {
		$this->Mes = str_pad($mes, 2, '0', STR_PAD_LEFT);;
	}

	/**
	 * @method setAno
	 * @access public
	 * @param int(2) $ano O parâmetro deverá conter o ano de validade do cartão do portador com duas posições (FORMATO AA).
	 */
	public function setAno($ano) {
		$this->Ano = str_pad($ano, 2, '0', STR_PAD_LEFT);
	}

	/**
	 * @method setDataExpiracao
	 *
	 * Formatos aceitos: mm/YY ou mm/YYYY
	 *
	 * @param string $data
	 */
	public function setDataExpiracao($data) {
	    $dataValidade = explode('/', $data);
	    $mes = $dataValidade[0];
	    if(strlen($dataValidade[1]) > 2) {
	        $year = date_create_from_format('Y', $dataValidade[1]);
	        $ano = $year->format('y');
	    } else {
	        $ano = $dataValidade[1];
	    }

	    $this->setAno($ano);
	    $this->setMes($mes);
	}

	public static function validCreditCard($number, $type = null) {
	    $ret = new \stdClass();
	    $ret->valid = false;
	    $ret->number = null;
	    $ret->type = null;

	    // Strip non-numeric characters
	    $number = preg_replace('/[^0-9]/', '', $number);
	    if (empty($type)) {
	        $type = self::creditCardType($number);
	    }
	    if (array_key_exists($type, self::$cards) && self::validCard($number, $type)) {
	        $ret->valid = true;
	        $ret->number = $number;
	        $ret->type = $type;
	        return $ret;
	    }
	    return $ret;
	}

	public static function validCvc($cvc, $type) {
	    return (ctype_digit($cvc) && array_key_exists($type, self::$cards) && self::validCvcLength($cvc, $type));
	}

	public static function validDate($year, $month) {
	    $month = str_pad($month, 2, '0', STR_PAD_LEFT);
	    if (! preg_match('/^20\d\d$/', $year)) {
	        return false;
	    }
	    if (! preg_match('/^(0[1-9]|1[0-2])$/', $month)) {
	        return false;
	    }
	    // past date
	    if ($year < date('Y') || $year == date('Y') && $month < date('m')) {
	        return false;
	    }
	    return true;
	}

	protected static function creditCardType($number) {
	    foreach (self::$cards as $type => $card) {
	        if (preg_match($card['pattern'], $number)) {
	            return $type;
	        }
	    }
	    return '';
	}

	protected static function validCard($number, $type) {
	    return (self::validPattern($number, $type) && self::validLength($number, $type) && self::validLuhn($number, $type));
	}

	protected static function validPattern($number, $type) {
	    $cards = Komerci::cards();
	    return preg_match($cards[$type]['pattern'], $number);
	}

	protected static function validLength($number, $type) {
	    $cards = Komerci::cards();
	    foreach ($cards[$type]['length'] as $length) {
	        if (strlen($number) == $length) {
	            return true;
	        }
	    }
	    return false;
	}

	protected static function validCvcLength($cvc, $type) {
	    $cards = Komerci::cards();
	    foreach ($cards[$type]['cvcLength'] as $length) {
	        if (strlen($cvc) == $length) {
	            return true;
	        }
	    }
	    return false;
	}

	protected static function validLuhn($number, $type) {
	    $cards = Komerci::cards();
	    if (!$cards[$type]['luhn']) {
	        return true;
	    } else {
	        return Komerci::luhnCheck($number);
	    }
	}

	protected static function luhnCheck($number) {
	    $checksum = 0;
	    for ($i=(2-(strlen($number) % 2)); $i<=strlen($number); $i+=2) {
	        $checksum += (int) ($number{$i-1});
	    }
	    // Analyze odd digits in even length strings or even digits in odd length strings.
	    for ($i=(strlen($number)% 2) + 1; $i<strlen($number); $i+=2) {
	        $digit = (int) ($number{$i-1}) * 2;
	        if ($digit < 10) {
	            $checksum += $digit;
	        } else {
	            $checksum += ($digit-9);
	        }
	    }
	    if (($checksum % 10) == 0) {
	        return true;
	    } else {
	        return false;
	    }
	}

	protected static function cards() {
	    return array(
	        // Debit cards must come first, since they have more specific patterns than their credit-card equivalents.
	        'visaelectron' => array(
	            'type' => 'visaelectron',
	            'pattern' => '/^4(026|17500|405|508|844|91[37])/',
	            'length' => array(16),
	            'cvcLength' => array(3),
	            'luhn' => true,
	        ),
	        'maestro' => array(
	            'type' => 'maestro',
	            'pattern' => '/^(5(018|0[23]|[68])|6(39|7))/',
	            'length' => array(12, 13, 14, 15, 16, 17, 18, 19),
	            'cvcLength' => array(3),
	            'luhn' => true,
	        ),
	        'forbrugsforeningen' => array(
	            'type' => 'forbrugsforeningen',
	            'pattern' => '/^600/',
	            'length' => array(16),
	            'cvcLength' => array(3),
	            'luhn' => true,
	        ),
	        'dankort' => array(
	            'type' => 'dankort',
	            'pattern' => '/^5019/',
	            'length' => array(16),
	            'cvcLength' => array(3),
	            'luhn' => true,
	        ),
	        // Credit cards
	        'visa' => array(
	            'type' => 'visa',
	            'pattern' => '/^4/',
	            'length' => array(13, 16),
	            'cvcLength' => array(3),
	            'luhn' => true,
	        ),
	        'mastercard' => array(
	            'type' => 'mastercard',
	            'pattern' => '/^(5[0-5]|2[2-7])/',
	            'length' => array(16),
	            'cvcLength' => array(3),
	            'luhn' => true,
	        ),
	        'amex' => array(
	            'type' => 'amex',
	            'pattern' => '/^3[47]/',
	            'format' => '/(\d{1,4})(\d{1,6})?(\d{1,5})?/',
	            'length' => array(15),
	            'cvcLength' => array(3, 4),
	            'luhn' => true,
	        ),
	        'dinersclub' => array(
	            'type' => 'dinersclub',
	            'pattern' => '/^3[0689]/',
	            'length' => array(14),
	            'cvcLength' => array(3),
	            'luhn' => true,
	        ),
	        'discover' => array(
	            'type' => 'discover',
	            'pattern' => '/^6([045]|22)/',
	            'length' => array(16),
	            'cvcLength' => array(3),
	            'luhn' => true,
	        ),
	        'unionpay' => array(
	            'type' => 'unionpay',
	            'pattern' => '/^(62|88)/',
	            'length' => array(16, 17, 18, 19),
	            'cvcLength' => array(3),
	            'luhn' => false,
	        ),
	        'jcb' => array(
	            'type' => 'jcb',
	            'pattern' => '/^35/',
	            'length' => array(16),
	            'cvcLength' => array(3),
	            'luhn' => true,
	        ),
	        'hipercard' => array(
	            'type' => 'hippercard',
	            'pattern' => '/^(606282\d{10}(\d{3})?)|(3841\d{15})/',
	            'length' => array(15),
	            'cvcLength' => array(3),
	            'luhn' => true,
	        ),
	        'elo' => array(
	            'type' => 'elo',
	            'pattern' => '/^([6362]{4})([0-9]{12})/',
	            'length' => array(16),
	            'cvcLength' => array(3),
	            'luhn' => true,
	        )
	    );
	}
}

?>