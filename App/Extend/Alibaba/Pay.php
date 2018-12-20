<?php

namespace App\Extend\Alibaba;

use Omnipay\Omnipay;

class Pay {
    const TEST_APP_ID = '2016080300154522';
    const TEST_APP_PRIVATE_KEY = 'MIIEowIBAAKCAQEA2Yy8+VsXT5dCetGM6QzVgs6wYesTtybDPHMsU9d1RbBu7efb2rHLqfY1S1pnRXRcA2CseFGLEAvYLGLn6VEIP0VkLNHSQz4XaiF7GdsD2nHFMJqJ1+GkDKtS6hcS9Ox6Pb5Yti1P8ti3P6T2HvsYE1Yw3ideU27NEIkh0nBUk7vcdy35DOku0z190LehLFsMBnLhzfVHAEjua4jZu5ev8SpVFPahbL88lECw9VTztTgfbQ1iVZmVwbbYuKmcmjOqsgoAqt+6sBJpKNMeAI5BCmNrS2I2ghtZpbFueizxmpboq5z6XWuSOnIqqcVUEg3CdCYdyUNM7srCZLxTdD33VwIDAQABAoIBAQCiyApG0wAYT+gwmkfDwhSo8htMyWdRLjH8M3nBqoBXivMWFN2PQGZSYKX0IksPz0diR54F0Y4qjZJNdBxvmnB/V17jooSgR/+hLDg+WoEBnQudmKT34iCsUi2Sd315wtCdDqa2eiVOYxaLCtYWzG9xWJbsQ6zy5P+QFew22FKQzylzW5XYyE21CCeuERWeQIK+ZBSHnFGJtoz/nIhtkYemVPHWi6R1IxAuDWMAF7t94l+qbXRBE+duJVxfeybdLr+WvkEgbAyY2zEYglu4YgETCZVy9K27C+PiEDnNHVIjWw7ORJ1CdEstcw6o7dYTpfsyvxI1U275/Mhtv+vFFdTBAoGBAPVKtZc0XGVqwt5c+GZ8RCsxMPdqRYnIUyjD+M4DC73DLbDrgu+HRlKkG1FEiXpYni7yo9J+aMnr+oZPCCWiv6nLXcbOOd0pI/ebUQHmHyYkAxzSKtl+FL0HCMLzdhyIIoje4TgPFZ4fgTDXf0KpHeaIXOdKXGhnNzWaLIfQTr5HAoGBAOML/mJ4bi6VcCr2SQzwekUvo9Cfd2+XKNhdHyNLvhD7IFEeKFknvjwCDesG4vKFDM1HHEZgMe5/lXKjpIsAPGbb411w3vi94gToEJsSKpBCq4/XTw+BCVerHUDtonH2tPibBwT0e93GBj5NnqSpDdnu+BO3DgtfaVsvEFXvXzZxAoGAPhumkxyM8Jjxsn/z3W9Fi3IvJdZguRxiAgZs7yzSQkAzR4K6ao/j/HTU/eTOso3Rr618UYX0XnxDuNI2C+GDiiiWHAqHmDocg0tuXX65EF059Ig6lUtZUOuBCmu+0kaeB+33NMyM+rabbZSDAovzqDWK4H1xnHXWAtqAv/q8lW0CgYABFZiUbdDQ5iPQALcembNryyt0Hp7aaJVoWyF/8KequxhPat50do0hCj14xuKkl+AUxucqquDyK6fSEVgC1fBz6U7vrk9STs3aiiUyKGcNo1Pgbv3QrCQYSBfurHPfKXd5zHu/GU/SIlT7TTGZxbjsNoj2xyQJB96f8dyZHHiO8QKBgAD9Uwcp7QczkqxdYRKsyGc+zIlTYubnScpgupqCdkHpgXEHg+Rfil8Gsj4YyjE0UK42RCP/wd75IZTE6dxYekv9N6LIlqySJblySnACCaaXsT1sLFxgs+0cj79hRDUZW4fj/+5vCr2uTKr6GVopwhp9s/iF3c0/SV0TA62DgbYD';
    const TEST_ALIPAY_PUBLIC_KEY = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA73Zc/+7XHJzKWwHb3Rf/SXJtdpMtvm5p6wVK0Qy6/tCkUxeCFmJ6KhpRllwYNqu0lH0v9HBTZO8E779tiXW/SOEBDZ/oSzyD2OdxD3udmUnE/olgSHQCQKmIkSl5SP7Ri6uEnXbKxdWh2FAoRgWSZ/3QpoVLUaL9v4AEHCG5tBA3Km77fAvGPnm0jIdb80SGAMcYJP+m4GkJ9k0AENUJpOa4xVF1thcwt96TZDyF1Zme96WVv3220U+Ymr4PGPsM+Z1qRkXM4ZlyVv0iwus2/WkJAyuPFaw/RJK3cqAe9kZw6eakCLnNF+LzlIh9DJq4Xe+yISDxh+poDkE9IrbahwIDAQAB';

    public static function getGateway () {
        $gateway = Omnipay::create('Alipay_AopPage');
        $gateway->setSignType('RSA2'); //RSA/RSA2
        $gateway->setAppId(self::TEST_APP_ID);
        $gateway->setPrivateKey(self::TEST_APP_PRIVATE_KEY);
        $gateway->setAlipayPublicKey(self::TEST_ALIPAY_PUBLIC_KEY);
        $gateway->setEnvironment('sandbox');
        return $gateway;
    }
}