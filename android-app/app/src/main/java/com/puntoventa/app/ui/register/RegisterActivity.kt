package com.puntoventa.app.ui.register

import android.content.Intent
import android.os.Bundle
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.puntoventa.app.R
import com.puntoventa.app.api.RetrofitClient
import com.puntoventa.app.api.models.RegisterRequest
import com.puntoventa.app.databinding.ActivityRegisterBinding
import com.puntoventa.app.ui.providers.ProvidersActivity
import com.puntoventa.app.utils.TokenManager
import kotlinx.coroutines.launch

class RegisterActivity : AppCompatActivity() {
    private lateinit var binding: ActivityRegisterBinding
    private lateinit var tokenManager: TokenManager
    
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityRegisterBinding.inflate(layoutInflater)
        setContentView(binding.root)
        
        tokenManager = TokenManager(this)
        
        // Configurar Spinner de roles
        val roles = arrayOf("Vendedor", "Admin")
        val adapter = android.widget.ArrayAdapter(this, android.R.layout.simple_spinner_item, roles)
        adapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item)
        binding.spRole.adapter = adapter
        
        binding.btnRegister.setOnClickListener {
            register()
        }
    }
    
    private fun register() {
        val name = binding.etName.text.toString().trim()
        val email = binding.etEmail.text.toString().trim()
        val password = binding.etPassword.text.toString()
        val role = binding.spRole.selectedItem.toString().lowercase()
        
        if (name.isEmpty() || email.isEmpty() || password.isEmpty()) {
            Toast.makeText(this, "Por favor completa todos los campos", Toast.LENGTH_SHORT).show()
            return
        }
        
        if (password.length < 6) {
            Toast.makeText(this, "La contraseña debe tener al menos 6 caracteres", Toast.LENGTH_SHORT).show()
            return
        }
        
        binding.btnRegister.isEnabled = false
        binding.progressBar.visibility = android.view.View.VISIBLE
        
        lifecycleScope.launch {
            try {
                val response = RetrofitClient.apiService.register(
                    RegisterRequest(name, email, password, role)
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
                    
                    Toast.makeText(this@RegisterActivity, "Registro exitoso", Toast.LENGTH_SHORT).show()
                    navigateToProviders()
                } else {
                    val errorBody = response.errorBody()?.string() ?: "Error desconocido"
                    Toast.makeText(this@RegisterActivity, "Error: $errorBody", Toast.LENGTH_LONG).show()
                }
            } catch (e: Exception) {
                Toast.makeText(this@RegisterActivity, "Error de conexión: ${e.message}", Toast.LENGTH_LONG).show()
            } finally {
                binding.btnRegister.isEnabled = true
                binding.progressBar.visibility = android.view.View.GONE
            }
        }
    }
    
    private fun navigateToProviders() {
        startActivity(Intent(this, ProvidersActivity::class.java))
        finish()
    }
}

