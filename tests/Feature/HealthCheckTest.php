<?php

test('the health check endpoint responds successfully', function () {
    $response = $this->get('/up');

    $response->assertStatus(200);
});
