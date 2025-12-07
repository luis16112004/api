package com.puntoventa.app.api

import com.puntoventa.app.api.models.*
import retrofit2.Response
import retrofit2.http.*

interface ApiService {
    
    // Autenticación
    @POST("api/register")
    suspend fun register(@Body request: RegisterRequest): Response<AuthResponse>
    
    @POST("api/login")
    suspend fun login(@Body request: LoginRequest): Response<AuthResponse>
    
    @POST("api/logout")
    suspend fun logout(@Header("Authorization") token: String): Response<Map<String, String>>
    
    // Proveedores
    @GET("api/providers")
    suspend fun getProviders(@Header("Authorization") token: String): Response<ProvidersResponse>
    
    @GET("api/providers/{id}")
    suspend fun getProvider(
        @Header("Authorization") token: String,
        @Path("id") id: String
    ): Response<ApiResponse<Provider>>
    
    @POST("api/providers")
    suspend fun createProvider(
        @Header("Authorization") token: String,
        @Body provider: Provider
    ): Response<ApiResponse<Provider>>
    
    @PUT("api/providers/{id}")
    suspend fun updateProvider(
        @Header("Authorization") token: String,
        @Path("id") id: String,
        @Body provider: Provider
    ): Response<ApiResponse<Provider>>
    
    @DELETE("api/providers/{id}")
    suspend fun deleteProvider(
        @Header("Authorization") token: String,
        @Path("id") id: String
    ): Response<Map<String, String>>
}

