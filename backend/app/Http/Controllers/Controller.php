<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: "Ormavers API Documentation",
    version: "1.0.0",
    description: "Dokumentasi RESTful API Ormavers - Platform Kolaborasi & Sinergi Organisasi Mahasiswa.",
    contact: new OA\Contact(name: "Tim Pengembang Ormavers")
)]
#[OA\Server(
    url: "/",
    description: "Current Domain Server"
)]
#[OA\Server(
    url: "http://127.0.0.1:8000",
    description: "Local Development Server"
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "Sanctum",
    description: "Gunakan token Sanctum dengan format: Bearer {token}"
)]
abstract class Controller
{
    //
}

