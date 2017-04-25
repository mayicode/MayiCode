# 模板内ajax请求

```php
$url = Url::to(["broadcast/delete"],true);
$js = <<<EOT
    $('a#delete').on('click', function(e) {
       $.ajax({
            url: '$url',
            type: 'post',
            data: {id:$(this).attr('date_value')},
            success: function (data) {
                location.reload();
            }
        });
    });
EOT;
$this->registerJs($js);
```
