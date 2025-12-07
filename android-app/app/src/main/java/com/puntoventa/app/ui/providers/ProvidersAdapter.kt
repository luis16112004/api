package com.puntoventa.app.ui.providers

import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.TextView
import androidx.recyclerview.widget.RecyclerView
import com.puntoventa.app.R
import com.puntoventa.app.api.models.Provider

class ProvidersAdapter(
    private var providers: List<Provider>
) : RecyclerView.Adapter<ProvidersAdapter.ProviderViewHolder>() {
    
    class ProviderViewHolder(itemView: View) : RecyclerView.ViewHolder(itemView) {
        val tvCompanyName: TextView = itemView.findViewById(R.id.tvCompanyName)
        val tvContactName: TextView = itemView.findViewById(R.id.tvContactName)
        val tvEmail: TextView = itemView.findViewById(R.id.tvEmail)
        val tvPhone: TextView = itemView.findViewById(R.id.tvPhone)
        val tvAddress: TextView = itemView.findViewById(R.id.tvAddress)
    }
    
    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ProviderViewHolder {
        val view = LayoutInflater.from(parent.context)
            .inflate(R.layout.item_provider, parent, false)
        return ProviderViewHolder(view)
    }
    
    override fun onBindViewHolder(holder: ProviderViewHolder, position: Int) {
        val provider = providers[position]
        holder.tvCompanyName.text = provider.companyName
        holder.tvContactName.text = "Contacto: ${provider.contactName}"
        holder.tvEmail.text = provider.email
        holder.tvPhone.text = provider.phoneNumber
        holder.tvAddress.text = "${provider.address}, ${provider.city}, ${provider.state}"
    }
    
    override fun getItemCount(): Int = providers.size
    
    fun updateProviders(newProviders: List<Provider>) {
        providers = newProviders
        notifyDataSetChanged()
    }
}

