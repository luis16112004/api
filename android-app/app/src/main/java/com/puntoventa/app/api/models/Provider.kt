package com.puntoventa.app.api.models

data class Provider(
    val id: String? = null,
    val companyName: String,
    val contactName: String,
    val email: String,
    val phoneNumber: String,
    val address: String,
    val city: String,
    val state: String,
    val postalCode: String,
    val country: String,
    val userId: String? = null,
    val createdAt: String? = null,
    val updatedAt: String? = null
)

