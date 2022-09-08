# README #

GiServices Drupal8 modul. 

Skupni GI spletni servisi.

Primer uporabe:

```
#!php
public function buildForm(array $form, FormStateInterface $form_state) {

    $service = \Drupal::service('gi_services');
    drupal_set_message($service->sayHello('smart user'));
}
```

ali pa malo bolj resna funkcija:

```
#!php

public function buildForm(array $form, FormStateInterface $form_state) {

    $service = \Drupal::service('gi_services');
    
    $r=$service->shp2pg_version();
    if ($r['return status'] === 0){
      drupal_set_message($r['version']);  
    }
    else{
      drupal_set_message('ne najdem shp2pgsql','error');  
    }
}
```

nekateri servisi bodo dostopni tudi prek URL-ja, npr.:

```
http://localhost/work/stage2/stage2dev/api/shp2pg/version
```