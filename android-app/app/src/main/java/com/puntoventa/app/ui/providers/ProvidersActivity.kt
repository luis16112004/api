package com.puntoventa.app.ui.providers

import android.content.Intent
import android.os.Bundle
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import com.puntoventa.app.R
import com.puntoventa.app.api.RetrofitClient
import com.puntoventa.app.databinding.ActivityProvidersBinding
import com.puntoventa.app.MainActivity
import com.puntoventa.app.utils.TokenManager
import kotlinx.coroutines.launch

class ProvidersActivity : AppCompatActivity() {
    private lateinit var binding: ActivityProvidersBinding
    private lateinit var tokenManager: TokenManager
    private lateinit var adapter: ProvidersAdapter
    
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityProvidersBinding.inflate(layoutInflater)
        setContentView(binding.root)
        
        tokenManager = TokenManager(this)
        
        // Si no está logueado, ir a login
        if (!tokenManager.isLoggedIn()) {
            startActivity(Intent(this, MainActivity::class.java))
            finish()
            return
        }
        
        setupRecyclerView()
        setupListeners()
        loadProviders()
    }
    
    private fun setupRecyclerView() {
        adapter = ProvidersAdapter(emptyList())
        binding.rvProviders.layoutManager = LinearLayoutManager(this)
        binding.rvProviders.adapter = adapter
    }
    
    private fun setupListeners() {
        binding.fabAddProvider.setOnClickListener {
            startActivity(Intent(this, AddProviderActivity::class.java))
        }
        
        binding.swipeRefresh.setOnRefreshListener {
            loadProviders()
        }
        
        binding.btnLogout.setOnClickListener {
            logout()
        }
    }
    
    private fun loadProviders() {
        val token = tokenManager.getToken() ?: return
        
        binding.swipeRefresh.isRefreshing = true
        binding.progressBar.visibility = android.view.View.VISIBLE
        
        lifecycleScope.launch {
            try {
                val response = RetrofitClient.apiService.getProviders("Bearer $token")
                
                if (response.isSuccessful && response.body() != null) {
                    val providers = response.body()!!.data
                    adapter.updateProviders(providers)
                    
                    if (providers.isEmpty()) {
                        binding.tvEmpty.visibility = android.view.View.VISIBLE
                    } else {
                        binding.tvEmpty.visibility = android.view.View.GONE
                    }
                } else {
                    Toast.makeText(this@ProvidersActivity, "Error al cargar proveedores", Toast.LENGTH_SHORT).show()
                }
            } catch (e: Exception) {
                Toast.makeText(this@ProvidersActivity, "Error de conexión: ${e.message}", Toast.LENGTH_LONG).show()
            } finally {
                binding.swipeRefresh.isRefreshing = false
                binding.progressBar.visibility = android.view.View.GONE
            }
        }
    }
    
    private fun logout() {
        tokenManager.clear()
        startActivity(Intent(this, MainActivity::class.java))
        finish()
    }
    
    override fun onResume() {
        super.onResume()
        loadProviders()
    }
}

