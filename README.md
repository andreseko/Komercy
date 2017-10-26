KOMERCY
=======

Biblioteca de pagamentos via cartão de crédito REDECARD.

## Instalação
### Composer
Se você já conhece o **Composer**, adicione a dependência abaixo à diretiva *"require"* no seu **composer.json**:
```
"andreseko/Komercy": "1.0.*"
```

## Como Usar
### Lendo um arquivo de Retorno
```php
$komercy = new Komercy\Komercy('12345'); // Onde 1234 é o seu número de afiliado.

$komercy->setTotal(100.00); // Valor a processar
$komerci->setNumPedido(1234); // Numero de controle interno do seu sistema
$komerci->setPortador('Andre Goncalves'); // Nome do portador do cartão
$komerci->setNrcartao('1234 5678 9101 1121'); // Número do cartão de crédito
$komerci->setCodVerificador('123'); // Código de segurança do cartão
$komerci->setDataExpiracao('01/19'); // Data de validade do cartão de crédito. Formatos aceitos: mm/YY ou mm/YYYY.
$komerci->setNumParcelas(2); // Número de parcelas da compra.
$komerci->requestPayment();

```

## Licença
Este projeto esta sobre a licença MIT
