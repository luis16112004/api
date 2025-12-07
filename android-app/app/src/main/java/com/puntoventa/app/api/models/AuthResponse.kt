package com.puntoventa.app.api.models

data class AuthResponse(
    val message: String,
    val user: User,
    val token: String
)

data class LoginRequest(
    val email: String,
    val password: String
)

data class RegisterRequest(
    val name: String,
    val email: String,
    val password: String,
    val role: String? = "vendedor"
)

data class ApiResponse<T>(
    val message: String,
    val data: T
)

data class ProvidersResponse(
    val message: String,
    val data: List<Provider>
)

