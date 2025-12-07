package com.puntoventa.app.ui.providers

import android.os.Bundle
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.puntoventa.app.R
import com.puntoventa.app.api.RetrofitClient
import com.puntoventa.app.api.models.Provider
import com.puntoventa.app.databinding.ActivityAddProviderBinding
import com.puntoventa.app.utils.TokenManager
import kotlinx.coroutines.launch

class AddProviderActivity : AppCompatActivity() {
    private lateinit var binding: ActivityAddProviderBinding
    private lateinit var tokenManager: TokenManager
    
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityAddProviderBinding.inflate(layoutInflater)
        setContentView(binding.root)
        
        tokenManager = TokenManager(this)
        
        binding.btnSave.setOnClickListener {
            saveProvider()
        }
    }
    
    private fun saveProvider() {
        val companyName = binding.etCompanyName.text.toString().trim()
        val contactName = binding.etContactName.text.toString().trim()
        val email = binding.etEmail.text.toString().trim()
        val phoneNumber = binding.etPhoneNumber.text.toString().trim()
        val address = binding.etAddress.text.toString().trim()
        val city = binding.etCity.text.toString().trim()
        val state = binding.etState.text.toString().trim()
        val postalCode = binding.etPostalCode.text.toString().trim()
        val country = binding.etCountry.text.toString().trim()
        
        // Validaciones básicas
        if (companyName.isEmpty() || contactName.isEmpty() || email.isEmpty() || 
            phoneNumber.isEmpty() || address.isEmpty() || city.isEmpty() || 
            state.isEmpty() || postalCode.isEmpty() || country.isEmpty()) {
            Toast.makeText(this, "Por favor completa todos los campos", Toast.LENGTH_SHORT).show()
            return
        }
        
        if (phoneNumber.length != 10) {
            Toast.makeText(this, "El teléfono debe tener 10 dígitos", Toast.LENGTH_SHORT).show()
            return
        }
        
        val token = tokenManager.getToken() ?: return
        
        binding.btnSave.isEnabled = false
        binding.progressBar.visibility = android.view.View.VISIBLE
        
        val provider = Provider(
            companyName = companyName,
            contactName = contactName,
            email = email,
            phoneNumber = phoneNumber,
            address = address,
            city = city,
            state = state,
            postalCode = postalCode,
            country = country,
            userId = tokenManager.getUserId()
        )
        
        lifecycleScope.launch {
            try {
                val response = RetrofitClient.apiService.createProvider("Bearer $token", provider)
                
                if (response.isSuccessful && response.body() != null) {
                    Toast.makeText(this@AddProviderActivity, "Proveedor creado exitosamente", Toast.LENGTH_SHORT).show()
                    finish()
                } else {
                    val errorBody = response.errorBody()?.string() ?: "Error desconocido"
                    Toast.makeText(this@AddProviderActivity, "Error: $errorBody", Toast.LENGTH_LONG).show()
                }
            } catch (e: Exception) {
                Toast.makeText(this@AddProviderActivity, "Error de conexión: ${e.message}", Toast.LENGTH_LONG).show()
            } finally {
                binding.btnSave.isEnabled = true
                binding.progressBar.visibility = android.view.View.GONE
            }
        }
    }
}

