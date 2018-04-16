# Fegg Tool Mail

The extends class that sending Japanese mail for [Fegg](https://github.com/genies-inc/Fegg)

## Example

```php
$mail = $this->getClass('Tool/Mail');

$mail->subject($subject);
$mail->body($body);
$mail->pushHeader($header);

// or

$mail = new Tool_Mail($subject, $body, $header);

$mail->send($receiveAddress, $senderName, $senderAddress);
```
