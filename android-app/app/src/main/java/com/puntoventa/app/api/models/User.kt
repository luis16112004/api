package com.puntoventa.app.api.models

data class User(
    val id: String? = null,
    val name: String,
    val email: String,
    val role: String? = "vendedor"
)

