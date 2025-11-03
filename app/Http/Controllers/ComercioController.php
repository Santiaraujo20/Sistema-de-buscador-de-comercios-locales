<?php

namespace App\Http\Controllers;

use App\Models\Comercio;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ComercioController extends Controller
{
    /**
     * Muestra la lista pública de comercios (con filtros).
     */
    public function index(Request $request): View
    {
        // Iniciar la consulta con los más nuevos primero
        $comercios = Comercio::latest()
            // Si el término de búsqueda existe, filtrar por nombre y descripción
            ->when($request->input('search'), function ($query, $searchTerm) {
                $query->where('nombre', 'like', "%{$searchTerm}%")
                      ->orWhere('descripcion', 'like', "%{$searchTerm}%");
            })
            // Si el rubro existe, filtrar por rubro
            ->when($request->input('rubro'), function ($query, $rubro) {
                $query->where('rubro', $rubro);
            })
            // Obtener 12 resultados por página (quitamos withQueryString para evitar errores del IDE)
            ->paginate(12);

        // Enviar los comercios y los filtros a la vista
        return view('comercios.index', [
            'comercios' => $comercios,
            'filters' => $request->only(['search', 'rubro'])
        ]);
    }

    /**
     * Muestra el formulario de registro del comercio.
     */
    public function create(): View|RedirectResponse
    {
        if (Auth::user()->comercio) {
            return redirect()->route('comercio.edit')->with('status', 'Ya tienes un comercio registrado. Aquí puedes editarlo.');
        }
        // CAMBIO CRÍTICO: Usar 'comercios.create' (plural)
        return view('comercios.create');
    }

    /**
     * Guarda el nuevo comercio en la base de datos.
     */
    public function store(Request $request): RedirectResponse
    {
        // 1. VALIDACIÓN DE DATOS
        $validatedData = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'direccion' => ['required', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'descripcion' => ['nullable', 'string'],
            'rubro' => ['required', 'string', 'max:100'],
            'horarios_atencion' => ['nullable', 'string', 'max:255'],
            'dias_no_laborales' => ['nullable', 'string', 'max:255'],
            'formas_pago' => ['nullable', 'string', 'max:255'],
            'servicios_adicionales' => ['nullable', 'string'],
            'sitio_web' => ['nullable', 'string', 'url', 'max:255'],
            'red_instagram' => ['nullable', 'string', 'max:100'],
            'red_facebook' => ['nullable', 'string', 'max:100'],
            'red_whatsapp' => ['nullable', 'string', 'max:50'],
        ]);

        // 2. PROCESAR LOS CHECKBOXES
        $validatedData['ingreso_discapacitados'] = $request->has('ingreso_discapacitados');
        $validatedData['estacionamiento'] = $request->has('estacionamiento');

        // 3. GUARDAR EL COMERCIO
        $request->user()->comercio()->create($validatedData);

        // 4. REDIRIGIR AL USUARIO
        return redirect()->route('dashboard')->with('status', '¡Tu comercio ha sido registrado con éxito!');
    }

    // -------------------------------------------------------------------
    // --- MÉTODO DE VISTA PÚBLICA ---
    // -------------------------------------------------------------------

    /**
     * Muestra el perfil público del comercio.
     */
    public function show(Comercio $comercio): View
    {
        // Laravel automáticamente encuentra el comercio usando el ID de la URL
        return view('comercios.show', [ // Usamos 'comercios.show' (plural)
            'comercio' => $comercio
        ]);
    }


    // -------------------------------------------------------------------
    // --- MÉTODOS DE MODIFICACIÓN ---
    // -------------------------------------------------------------------

    /**
     * Muestra el formulario para editar el comercio existente.
     */
    public function edit(): View|RedirectResponse
    {
        $comercio = Auth::user()->comercio;

        if (!$comercio) {
            return redirect()->route('comercio.create')->with('status', 'Primero debes registrar tu comercio.');
        }

        // CAMBIO CRÍTICO: Usar 'comercios.edit' (plural)
        return view('comercios.edit', [
            'comercio' => $comercio
        ]);
    }

    /**
     * Actualiza el comercio en la base de datos.
     */
    public function update(Request $request): RedirectResponse
    {
        $comercio = $request->user()->comercio;

        if (!$comercio) {
            return redirect()->route('comercio.create')->with('status', 'Primero debes registrar tu comercio.');
        }

        // 1. VALIDACIÓN DE DATOS
        $validatedData = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'direccion' => ['required', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'descripcion' => ['nullable', 'string'],
            'rubro' => ['required', 'string', 'max:100'],
            'horarios_atencion' => ['nullable', 'string', 'max:255'],
            'dias_no_laborales' => ['nullable', 'string', 'max:255'],
            'formas_pago' => ['nullable', 'string', 'max:255'],
            'servicios_adicionales' => ['nullable', 'string'],
            'sitio_web' => ['nullable', 'string', 'url', 'max:255'],
            'red_instagram' => ['nullable', 'string', 'max:100'],
            'red_facebook' => ['nullable', 'string', 'max:100'],
            'red_whatsapp' => ['nullable', 'string', 'max:50'],
        ]);

        // 2. PROCESAR LOS CHECKBOXES
        $validatedData['ingreso_discapacitados'] = $request->has('ingreso_discapacitados');
        $validatedData['estacionamiento'] = $request->has('estacionamiento');

        // 3. ACTUALIZAR EL COMERCIO
        $comercio->update($validatedData);

        // 4. REDIRIGIR AL USUARIO
        return redirect()->route('dashboard')->with('status', '¡Tu comercio ha sido actualizado con éxito!');
    }

    // -------------------------------------------------------------------
    // --- MÉTODO DE ELIMINACIÓN ---
    // -------------------------------------------------------------------

    /**
     * Elimina el comercio de la base de datos.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $comercio = $request->user()->comercio;

        if (!$comercio) {
            return redirect()->route('dashboard')->with('status', 'No tienes ningún comercio para eliminar.');
        }

        // Eliminar el comercio
        $comercio->delete();

        // Redirigir al dashboard con un mensaje de éxito
        return redirect()->route('dashboard')->with('status', 'Tu comercio ha sido eliminado correctamente.');
    }
}
