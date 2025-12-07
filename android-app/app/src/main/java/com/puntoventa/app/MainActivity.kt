package com.puntoventa.app

import android.content.Intent
import android.os.Bundle
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.puntoventa.app.api.RetrofitClient
import com.puntoventa.app.api.models.LoginRequest
import com.puntoventa.app.databinding.ActivityMainBinding
import com.puntoventa.app.ui.providers.ProvidersActivity
import com.puntoventa.app.ui.register.RegisterActivity
import com.puntoventa.app.utils.TokenManager
import kotlinx.coroutines.launch

class MainActivity : AppCompatActivity() {
    private lateinit var binding: ActivityMainBinding
    private lateinit var tokenManager: TokenManager
    
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityMainBinding.inflate(layoutInflater)
        setContentView(binding.root)
        
        tokenManager = TokenManager(this)
        
        // Si ya está logueado, ir directo a proveedores
        if (tokenManager.isLoggedIn()) {
            navigateToProviders()
            return
        }
        
        setupListeners()
    }
    
    private fun setupListeners() {
        // Acceder directamente a las vistas a través del binding
        binding.btnLogin.setOnClickListener {
            login()
        }
        
        binding.tvRegister.setOnClickListener {
            startActivity(Intent(this, RegisterActivity::class.java))
        }
    }
    
    private fun login() {
        val email = binding.etEmail.text.toString().trim()
        val password = binding.etPassword.text.toString()
        
        if (email.isEmpty() || password.isEmpty()) {
            Toast.makeText(this, "Por favor completa todos los campos", Toast.LENGTH_SHORT).show()
            return
        }
        
        binding.btnLogin.isEnabled = false
        binding.progressBar.visibility = android.view.View.VISIBLE
        
        lifecycleScope.launch {
            try {
                val response = RetrofitClient.apiService.login(
                    LoginRequest(email, password)
                )
                
                if (response.isSuccessful && response.body() != null) {
                    val authResponse = response.body()!!
                    
                    // Guardar token y datos del usuario
                    tokenManager.saveToken(authResponse.token)
                    tokenManager.saveUser(
                        authResponse.user.id ?: "",
                        authResponse.user.name,
                        authResponse.user.email,
                        authResponse.user.role ?: "vendedor"
                    )
                    
                    Toast.makeText(this@MainActivity, "Login exitoso", Toast.LENGTH_SHORT).show()
                    navigateToProviders()
                } else {
                    val errorBody = response.errorBody()?.string() ?: "Error desconocido"
                    Toast.makeText(this@MainActivity, "Error: $errorBody", Toast.LENGTH_LONG).show()
                }
            } catch (e: Exception) {
                Toast.makeText(this@MainActivity, "Error de conexión: ${e.message}", Toast.LENGTH_LONG).show()
            } finally {
                binding.btnLogin.isEnabled = true
                binding.progressBar.visibility = android.view.View.GONE
            }
        }
    }
    
    private fun navigateToProviders() {
        startActivity(Intent(this, ProvidersActivity::class.java))
        finish()
    }
}

