<?php

describe('painel do Horizon', function () {
    test('o admin acessa', function () {
        $this->actingAs(admin())
            ->get(route('horizon.index'))
            ->assertOk();
    });

    test('o operador é barrado', function () {
        $this->actingAs(operador())
            ->get(route('horizon.index'))
            ->assertForbidden();
    });

    test('o visitante é barrado', function () {
        $this->get(route('horizon.index'))
            ->assertForbidden();
    });
});

describe('link na barra lateral', function () {
    test('aparece para o admin', function () {
        $this->actingAs(admin())
            ->get(route('dashboard'))
            ->assertSee('Filas (Horizon)');
    });

    test('não aparece para o operador', function () {
        $this->actingAs(operador())
            ->get(route('dashboard'))
            ->assertDontSee('Filas (Horizon)');
    });
});