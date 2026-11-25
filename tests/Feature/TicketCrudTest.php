<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ticket;
use App\Models\TicketLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class TicketCrudTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        if (empty(config('app.key'))) {
            config(['app.key' => 'base64:' . base64_encode(str_repeat('a', 32))]);
        }

        if (empty(config('jwt.secret'))) {
            config(['jwt.secret' => str_repeat('smai_jwt_test_secret_', 3)]);
        }

        $this->category = Category::create([
            'name' => 'Fallo de Red o Conexión',
            'description' => 'Problemas de conectividad local o internet predeterminados',
            'is_active' => true,
        ]);
    }

    private function authHeadersForUser(int $userId): array
    {
        $user = new User();
        $user->id = $userId;
        $token = JWTAuth::fromUser($user);

        return ['Authorization' => "Bearer {$token}"];
    }

    public function test_ticket_endpoints_require_auth_api_middleware(): void
    {
        $ticket = Ticket::create([
            'user_id' => 10,
            'category_id' => $this->category->id,
            'location' => 'Bloque B - Piso 2',
            'title' => 'Sin conexión',
            'description' => 'El equipo no conecta al Wi-Fi',
        ]);

        $this->getJson('/api/tickets')->assertStatus(401);
        $this->postJson('/api/tickets', [])->assertStatus(401);
        $this->getJson("/api/tickets/{$ticket->id}")->assertStatus(401);
        $this->putJson("/api/tickets/{$ticket->id}", ['description' => 'Actualizado'])->assertStatus(401);
        $this->patchJson("/api/tickets/{$ticket->id}", ['priority' => 'ALTA'])->assertStatus(401);
        $this->deleteJson("/api/tickets/{$ticket->id}")->assertStatus(401);

        $this->withHeaders(['Authorization' => 'Bearer invalid.jwt.token'])
            ->getJson('/api/tickets')
            ->assertStatus(401);
    }

    public function test_store_creates_ticket_with_authenticated_user_and_audit_log(): void
    {
        $response = $this->withHeaders($this->authHeadersForUser(42))
            ->postJson('/api/tickets', [
                'user_id' => 999, // Must be ignored in favor of JWT user
                'location' => 'Laboratorio 301',
                'category_id' => $this->category->id,
                'title' => 'Impresora desconectada',
                'description' => 'No responde en red de oficina',
                'priority' => 'alta',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Ticket creado con éxito')
            ->assertJsonPath('data.user_id', 42)
            ->assertJsonPath('data.location', 'Laboratorio 301')
            ->assertJsonPath('data.status', Ticket::STATUS_PENDIENTE_DIAGNOSTICO)
            ->assertJsonPath('data.priority', Ticket::PRIORITY_ALTA)
            ->assertJsonPath('data.category.id', $this->category->id);

        $ticketId = $response->json('data.id');

        $this->assertDatabaseHas('TK_tickets', [
            'id' => $ticketId,
            'user_id' => 42,
            'location' => 'Laboratorio 301',
            'status' => Ticket::STATUS_PENDIENTE_DIAGNOSTICO,
            'priority' => Ticket::PRIORITY_ALTA,
        ]);

        $this->assertDatabaseHas('TK_ticket_logs', [
            'ticket_id' => $ticketId,
            'user_id' => 42,
            'action' => 'CREACION',
        ]);

        // Nullable title and description remain supported per develop schema
        $this->withHeaders($this->authHeadersForUser(42))
            ->postJson('/api/tickets', [
                'location' => 'Sala de Juntas',
                'category_id' => $this->category->id,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.priority', Ticket::PRIORITY_MEDIA);
    }

    public function test_store_validates_required_and_invalid_fields(): void
    {
        $response = $this->withHeaders($this->authHeadersForUser(42))
            ->postJson('/api/tickets', [
                'category_id' => 99999,
                'priority' => 'CRITICA',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['location', 'category_id', 'priority']]);

        $this->withHeaders($this->authHeadersForUser(42))
            ->postJson('/api/tickets', [
                'location' => 'Oficina 102',
                'category_id' => $this->category->id,
                'priority' => '',
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['priority']]);
    }

    public function test_index_lists_and_filters_tickets(): void
    {
        $otherCategory = Category::create([
            'name' => 'Fallo de Encendido / Hardware',
            'description' => 'Fallas eléctricas',
            'is_active' => true,
        ]);

        Ticket::create([
            'user_id' => 10,
            'assigned_to' => 200,
            'category_id' => $this->category->id,
            'location' => 'Edificio A - Piso 2',
            'title' => 'Falla Wi-Fi piso 2',
            'description' => 'Sin señal inalámbrica',
            'status' => Ticket::STATUS_EN_PROCESO,
            'priority' => Ticket::PRIORITY_MEDIA,
        ]);

        Ticket::create([
            'user_id' => 20,
            'assigned_to' => null,
            'category_id' => $otherCategory->id,
            'location' => 'Biblioteca Central',
            'title' => 'PC no arranca',
            'description' => 'Fuente de poder quemada',
            'status' => Ticket::STATUS_PENDIENTE_DIAGNOSTICO,
            'priority' => Ticket::PRIORITY_ALTA,
        ]);

        $headers = $this->authHeadersForUser(10);

        $this->withHeaders($headers)
            ->getJson('/api/tickets')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');

        $this->withHeaders($headers)
            ->getJson('/api/tickets?status=EN_PROCESO&priority=MEDIA')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Falla Wi-Fi piso 2');

        $this->withHeaders($headers)
            ->getJson('/api/tickets?status=EN_PROCESO,PENDIENTE_DIAGNOSTICO&priority=MEDIA,ALTA')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');

        $this->withHeaders($headers)
            ->getJson('/api/tickets?mine=true')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.user_id', 10);

        $this->withHeaders($headers)
            ->getJson('/api/tickets?unassigned=true')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'PC no arranca');

        $this->withHeaders($headers)
            ->getJson('/api/tickets?assigned_to=null')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'PC no arranca');

        $this->withHeaders($headers)
            ->getJson('/api/tickets?location=biblioteca')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.location', 'Biblioteca Central');

        $this->withHeaders($headers)
            ->getJson('/api/tickets?search=fuente')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'PC no arranca');

        $this->withHeaders($headers)
            ->getJson('/api/tickets?per_page=1&sort_by=priority&sort_dir=desc')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.priority', Ticket::PRIORITY_ALTA)
            ->assertJsonPath('meta.total', 2);

        $this->withHeaders($headers)
            ->getJson('/api/tickets?status=NO_EXISTE')
            ->assertStatus(422);
    }

    public function test_show_returns_ticket_detail_and_handles_not_found(): void
    {
        $ticket = Ticket::create([
            'user_id' => 15,
            'category_id' => $this->category->id,
            'location' => 'Sala Sistemas 1',
            'title' => 'Sin acceso VPN',
            'description' => 'Error de certificado',
            'priority' => Ticket::PRIORITY_MEDIA,
        ]);

        TicketLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => 15,
            'action' => 'CREACION',
            'comment' => 'Ticket inicializado',
        ]);

        $headers = $this->authHeadersForUser(15);

        $this->withHeaders($headers)
            ->getJson("/api/tickets/{$ticket->id}")
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $ticket->id)
            ->assertJsonPath('data.location', 'Sala Sistemas 1')
            ->assertJsonPath('data.category.name', 'Fallo de Red o Conexión')
            ->assertJsonCount(1, 'data.logs');

        $this->withHeaders($headers)
            ->getJson('/api/tickets/999999')
            ->assertStatus(404)
            ->assertJsonPath('success', false);

        $this->withHeaders($headers)
            ->getJson('/api/tickets/not-a-number')
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_support_agent_can_assign_ticket_to_self_and_escalate_it(): void
    {
        $ticket = Ticket::create([
            'user_id' => 10,
            'category_id' => $this->category->id,
            'location' => 'Sala de soporte',
            'title' => 'Falla crítica',
            'status' => Ticket::STATUS_PENDIENTE_DIAGNOSTICO,
        ]);

        $this->withHeaders($this->authHeadersForUser(77))
            ->postJson("/api/tickets/{$ticket->id}/assign", [
                'assigned_to' => 999,
                'status' => Ticket::STATUS_EN_PROCESO,
            ])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.assigned_to', 77)
            ->assertJsonPath('data.status', Ticket::STATUS_ESCALADO);

        $this->assertDatabaseHas('TK_tickets', [
            'id' => $ticket->id,
            'assigned_to' => 77,
            'status' => Ticket::STATUS_ESCALADO,
        ]);
        $this->assertDatabaseHas('TK_ticket_logs', [
            'ticket_id' => $ticket->id,
            'user_id' => 77,
            'action' => 'ASIGNACION',
        ]);
        $this->assertDatabaseHas('TK_ticket_logs', [
            'ticket_id' => $ticket->id,
            'user_id' => 77,
            'action' => 'ESCALAMIENTO',
        ]);
    }

    public function test_assign_ticket_requires_authentication_and_returns_not_found(): void
    {
        $this->postJson('/api/tickets/1/assign')->assertStatus(401);

        $this->withHeaders($this->authHeadersForUser(77))
            ->postJson('/api/tickets/999999/assign')
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_assigned_user_can_record_ticket_cause_and_solution(): void
    {
        $ticket = Ticket::create([
            'user_id' => 10,
            'assigned_to' => 77,
            'category_id' => $this->category->id,
            'location' => 'Sala de soporte',
            'title' => 'Equipo sin energía',
            'status' => Ticket::STATUS_ESCALADO,
        ]);

        $this->withHeaders($this->authHeadersForUser(77))
            ->patchJson("/api/tickets/{$ticket->id}/resolution", [
                'cause' => 'Fuente de alimentación defectuosa',
                'solution' => 'Se reemplazó la fuente de alimentación.',
                'assigned_to' => 88,
                'status' => Ticket::STATUS_CERRADO,
            ])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.cause', 'Fuente de alimentación defectuosa')
            ->assertJsonPath('data.solution', 'Se reemplazó la fuente de alimentación.')
            ->assertJsonPath('data.assigned_to', 77)
            ->assertJsonPath('data.status', Ticket::STATUS_ESCALADO);

        $this->assertDatabaseHas('TK_tickets', [
            'id' => $ticket->id,
            'assigned_to' => 77,
            'cause' => 'Fuente de alimentación defectuosa',
            'solution' => 'Se reemplazó la fuente de alimentación.',
            'status' => Ticket::STATUS_ESCALADO,
        ]);
        $this->assertDatabaseHas('TK_ticket_logs', [
            'ticket_id' => $ticket->id,
            'user_id' => 77,
            'action' => 'ACTUALIZACION',
        ]);
    }

    public function test_only_the_assigned_user_can_record_ticket_resolution(): void
    {
        $ticket = Ticket::create([
            'user_id' => 10,
            'assigned_to' => 88,
            'category_id' => $this->category->id,
            'location' => 'Laboratorio',
        ]);

        $this->withHeaders($this->authHeadersForUser(77))
            ->patchJson("/api/tickets/{$ticket->id}/resolution", [
                'cause' => 'Cable desconectado',
                'solution' => 'Se conectó el cable.',
            ])
            ->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Solo el usuario asignado puede registrar la causa y la solución.');

        $this->assertDatabaseHas('TK_tickets', [
            'id' => $ticket->id,
            'assigned_to' => 88,
            'cause' => null,
            'solution' => null,
        ]);

        $unassignedTicket = Ticket::create([
            'user_id' => 10,
            'category_id' => $this->category->id,
            'location' => 'Sala sin asignar',
        ]);

        $this->withHeaders($this->authHeadersForUser(77))
            ->patchJson("/api/tickets/{$unassignedTicket->id}/resolution", [
                'cause' => 'Falla de red',
                'solution' => 'Se reinició el router.',
            ])
            ->assertStatus(403);

        $this->assertDatabaseHas('TK_tickets', [
            'id' => $unassignedTicket->id,
            'assigned_to' => null,
            'cause' => null,
            'solution' => null,
        ]);
    }

    public function test_resolution_endpoint_requires_authentication_and_both_fields(): void
    {
        $ticket = Ticket::create([
            'user_id' => 10,
            'assigned_to' => 77,
            'category_id' => $this->category->id,
            'location' => 'Oficina',
        ]);

        $this->patchJson("/api/tickets/{$ticket->id}/resolution", [])
            ->assertStatus(401);

        $this->withHeaders($this->authHeadersForUser(77))
            ->patchJson("/api/tickets/{$ticket->id}/resolution", [])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['cause', 'solution']]);

        $this->withHeaders($this->authHeadersForUser(77))
            ->patchJson("/api/tickets/{$ticket->id}/resolution", [
                'cause' => str_repeat('a', 256),
                'solution' => 'Solución registrada.',
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['cause']]);

        $this->withHeaders($this->authHeadersForUser(77))
            ->patchJson('/api/tickets/999999/resolution', [
                'cause' => 'Falla eléctrica',
                'solution' => 'Se reemplazó el adaptador.',
            ])
            ->assertStatus(404);
    }

    public function test_general_ticket_update_cannot_change_assignee_cause_or_solution(): void
    {
        $ticket = Ticket::create([
            'user_id' => 10,
            'assigned_to' => 88,
            'category_id' => $this->category->id,
            'location' => 'Recepción',
        ]);

        $this->withHeaders($this->authHeadersForUser(77))
            ->patchJson("/api/tickets/{$ticket->id}", [
                'assigned_to' => 77,
                'cause' => 'Intento de cambiar la causa',
                'solution' => 'Intento de cambiar la solución',
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['assigned_to', 'cause', 'solution']]);

        $this->assertDatabaseHas('TK_tickets', [
            'id' => $ticket->id,
            'assigned_to' => 88,
            'cause' => null,
            'solution' => null,
        ]);
    }

    public function test_update_modifies_status_priority_description_and_logs_traceability(): void
    {
        $ticket = Ticket::create([
            'user_id' => 10,
            'category_id' => $this->category->id,
            'location' => 'Sala A',
            'title' => 'Red lenta',
            'description' => 'Latencia alta en sala A',
            'status' => Ticket::STATUS_PENDIENTE_DIAGNOSTICO,
            'priority' => Ticket::PRIORITY_BAJA,
        ]);

        $supportHeaders = $this->authHeadersForUser(77);

        $this->withHeaders($supportHeaders)
            ->postJson("/api/tickets/{$ticket->id}/assign")
            ->assertStatus(200)
            ->assertJsonPath('data.assigned_to', 77);

        $response = $this->withHeaders($supportHeaders)
            ->putJson("/api/tickets/{$ticket->id}", [
                'user_id' => 999, // Creator must never be overwritten
                'status' => Ticket::STATUS_EN_PROCESO,
                'priority' => Ticket::PRIORITY_ALTA,
                'description' => 'Latencia alta afectando todo el piso',
                'comment' => 'Atendiendo incidente prioritario',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Ticket actualizado con éxito')
            ->assertJsonPath('data.user_id', 10)
            ->assertJsonPath('data.status', Ticket::STATUS_EN_PROCESO)
            ->assertJsonPath('data.priority', Ticket::PRIORITY_ALTA)
            ->assertJsonPath('data.description', 'Latencia alta afectando todo el piso')
            ->assertJsonPath('data.assigned_to', 77);

        $this->assertDatabaseHas('TK_ticket_logs', [
            'ticket_id' => $ticket->id,
            'user_id' => 77,
            'action' => 'CAMBIO_ESTADO',
        ]);
        $this->assertDatabaseHas('TK_ticket_logs', [
            'ticket_id' => $ticket->id,
            'user_id' => 77,
            'action' => 'CAMBIO_PRIORIDAD',
        ]);
        $this->assertDatabaseHas('TK_ticket_logs', [
            'ticket_id' => $ticket->id,
            'user_id' => 77,
            'action' => 'ASIGNACION',
        ]);
        $this->assertDatabaseHas('TK_ticket_logs', [
            'ticket_id' => $ticket->id,
            'user_id' => 77,
            'action' => 'ACTUALIZACION',
        ]);

        $this->withHeaders($supportHeaders)
            ->patchJson("/api/tickets/{$ticket->id}", [
                'status' => Ticket::STATUS_ESCALADO,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', Ticket::STATUS_ESCALADO)
            ->assertJsonPath('data.assigned_to', 77);

        $this->assertDatabaseHas('TK_ticket_logs', [
            'ticket_id' => $ticket->id,
            'user_id' => 77,
            'action' => 'ESCALAMIENTO',
        ]);

        $this->withHeaders($supportHeaders)
            ->getJson("/api/tickets/{$ticket->id}/logs")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->withHeaders($supportHeaders)
            ->patchJson("/api/tickets/{$ticket->id}", [
                'status' => Ticket::STATUS_CERRADO,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', Ticket::STATUS_CERRADO);

        $this->assertDatabaseHas('TK_ticket_logs', [
            'ticket_id' => $ticket->id,
            'user_id' => 77,
            'action' => 'CIERRE',
        ]);

        $this->withHeaders($supportHeaders)
            ->putJson("/api/tickets/{$ticket->id}", [
                'status' => null,
                'priority' => '',
                'location' => '',
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['status', 'priority', 'location']]);

        $this->withHeaders($supportHeaders)
            ->putJson("/api/tickets/{$ticket->id}", [])
            ->assertStatus(422);

        $this->withHeaders($supportHeaders)
            ->putJson('/api/tickets/999999', ['priority' => Ticket::PRIORITY_ALTA])
            ->assertStatus(404);
    }

    public function test_destroy_deletes_ticket_and_returns_404_when_missing(): void
    {
        $ticket = Ticket::create([
            'user_id' => 10,
            'category_id' => $this->category->id,
            'location' => 'Auditorio',
            'title' => 'Ticket temporal',
            'description' => 'Para eliminar',
        ]);

        TicketLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => 10,
            'action' => 'CREACION',
            'comment' => 'Creado',
        ]);

        $headers = $this->authHeadersForUser(10);

        $this->withHeaders($headers)
            ->deleteJson("/api/tickets/{$ticket->id}")
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Ticket eliminado con éxito');

        $this->assertDatabaseMissing('TK_tickets', ['id' => $ticket->id]);
        $this->assertDatabaseMissing('TK_ticket_logs', ['ticket_id' => $ticket->id]);

        $this->withHeaders($headers)
            ->deleteJson("/api/tickets/{$ticket->id}")
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }
}
